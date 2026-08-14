# Idempotent Chatwoot bootstrap. Runs inside the Rails image and writes only
# non-secret IDs plus the Agent Bot access token to the mounted runtime file.

require 'erb'
require 'fileutils'

account = Account.find_or_create_by!(name: ENV.fetch('CHATWOOT_ACCOUNT_NAME', 'Business Workspace')) do |record|
  record.locale = :th
  record.settings = {}
end

email = ENV.fetch('CHATWOOT_ADMIN_EMAIL', ENV.fetch('ADMIN_EMAIL', 'admin@local.invalid'))
password = ENV.fetch('CHATWOOT_ADMIN_PASSWORD', ENV.fetch('ADMIN_PASSWORD'))
user = User.find_or_initialize_by(email: email.downcase)
user.assign_attributes(
  name: ENV.fetch('CHATWOOT_ADMIN_NAME', 'Business Administrator'),
  password: password,
  password_confirmation: password,
  provider: 'email',
  uid: email.downcase,
  confirmed_at: Time.current
)
user.save!
account.account_users.find_or_create_by!(user: user) { |membership| membership.role = :administrator }

service_email = ENV.fetch('CHATWOOT_AI_SERVICE_EMAIL', 'ai-service@local.invalid')
service_password = ENV.fetch('CHATWOOT_AI_SERVICE_PASSWORD', password)
service_user = User.find_or_initialize_by(email: service_email.downcase)
service_user.assign_attributes(
  name: 'Business AI Service',
  password: service_password,
  password_confirmation: service_password,
  provider: 'email',
  uid: service_email.downcase,
  confirmed_at: Time.current
)
service_user.save!
account.account_users.find_or_create_by!(user: service_user) { |membership| membership.role = :agent }

team_name = ENV.fetch('CHATWOOT_HANDOFF_TEAM_NAME', 'Sales and Support').downcase
team = Team.find_or_create_by!(account: account, name: team_name) do |record|
  record.allow_auto_assign = true
end
# allow_auto_assign is what fills an unassigned team-routed conversation with
# a human once handoff() assigns the team; find_or_create_by only sets it on
# the create branch, so a pre-existing team from an older bootstrap needs this.
team.update!(allow_auto_assign: true) unless team.allow_auto_assign?
# The AI service account must stay out of the human round-robin pool, or a
# handed-off conversation can be auto-assigned right back to the bot.
TeamMember.where(team_id: team.id, user_id: service_user.id).destroy_all
TeamMember.find_or_create_by!(team_id: team.id, user_id: user.id)
service_access_token = service_user.access_token || service_user.create_access_token

# Return to AI (SPEC FR-OWN-005/FR-HO-006): staff apply RETURN_TO_AI_LABEL to
# hand a conversation back; the AI service applies HUMAN_HANDLING_LABEL on
# handoff so the state is visible in the conversation list. Titles must match
# services/ai/src/ai_service/main.py's HUMAN_HANDLING_LABEL/RETURN_TO_AI_LABEL
# exactly -- Chatwoot lowercases label titles on save, and the Python service
# only ever sends the lowercase form, so no mismatch in practice.
[
  ['human-handling', 'เจ้าหน้าที่กำลังดูแล (Human Active)'],
  ['return-to-ai', 'ส่งกลับให้ AI ดูแลต่อ (Return to AI)'],
].each do |title, description|
  Label.find_or_create_by!(title: title, account_id: account.id) do |record|
    record.description = description
    record.color = '#1f93ff'
  end
end

webhook_token = ENV.fetch('CHATWOOT_WEBHOOK_TOKEN')
ai_hostname = ENV.fetch('AI_HOSTNAME', '').strip
if ai_hostname.present?
  outgoing_url = "https://#{ai_hostname}/webhooks/chatwoot/#{ERB::Util.url_encode(webhook_token)}"
else
  outgoing_url = "http://ai:8000/webhooks/chatwoot/#{ERB::Util.url_encode(webhook_token)}"
end
bot = AgentBot.find_or_initialize_by(account: account, name: ENV.fetch('CHATWOOT_AGENT_BOT_NAME', 'Business AI'))
bot.outgoing_url = outgoing_url
bot.description = 'Business AI orchestrator; Chatwoot remains the conversation owner.'
bot.save!
access_token = bot.access_token || bot.create_access_token

line_id = ENV['LINE_CHANNEL_ID'].to_s.strip
line_secret = ENV['LINE_CHANNEL_SECRET'].to_s.strip
line_token = ENV['LINE_CHANNEL_ACCESS_TOKEN'].to_s.strip
inbox = nil
if line_id.present? && line_secret.present? && line_token.present?
  channel = Channel::Line.find_or_initialize_by(line_channel_id: line_id)
  channel.assign_attributes(account: account, line_channel_secret: line_secret, line_channel_token: line_token)
  channel.save!
  inbox = Inbox.find_or_create_by!(account: account, channel: channel) do |record|
    record.name = ENV.fetch('LINE_INBOX_NAME', 'LINE Business')
    record.enable_auto_assignment = false
  end
  inbox.update!(enable_auto_assignment: false)
  InboxMember.find_or_create_by!(inbox: inbox, user: service_user)
  AgentBotInbox.find_or_create_by!(account: account, agent_bot: bot, inbox: inbox) { |record| record.status = :active }
end

FileUtils.mkdir_p('/runtime')
File.open('/runtime/chatwoot_bot.env', File::WRONLY | File::CREAT | File::TRUNC, 0o600) do |file|
  file.write("CHATWOOT_ACCOUNT_ID=#{account.id}\n")
  file.write("CHATWOOT_HANDOFF_TEAM_ID=#{team.id}\n")
  file.write("CHATWOOT_API_TOKEN=#{service_access_token.token}\n")
  file.write("CHATWOOT_BOT_ACCESS_TOKEN=#{access_token.token}\n")
  file.write("CHATWOOT_ALLOWED_INBOX_IDS=#{inbox&.id}\n")
end

::Redis::Alfred.delete(::Redis::Alfred::CHATWOOT_INSTALLATION_ONBOARDING)

puts "chatwoot bootstrap complete account=#{account.id} team=#{team.id} line_inbox=#{inbox&.id || 'not_configured'}"
