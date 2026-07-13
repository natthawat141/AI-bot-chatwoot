import logging

from linebot.v3.messaging import (
    ApiClient,
    Configuration,
    LocationMessage,
    Message,
    MessagingApi,
    ReplyMessageRequest,
    TextMessage,
)

from app.config import Settings

logger = logging.getLogger("line_bot.line_client")


class LineClient:
    def __init__(self, settings: Settings):
        self._config = Configuration(access_token=settings.LINE_CHANNEL_ACCESS_TOKEN)

    def reply_text(self, reply_token: str, text: str) -> bool:
        return self._reply(reply_token, TextMessage(text=text))

    def reply_location(
        self,
        reply_token: str,
        *,
        title: str,
        address: str,
        latitude: float,
        longitude: float,
    ) -> bool:
        return self._reply(
            reply_token,
            LocationMessage(
                title=title,
                address=address,
                latitude=latitude,
                longitude=longitude,
            ),
        )

    def reply_message(self, reply_token: str, message: Message) -> bool:
        return self._reply(reply_token, message)

    def _reply(self, reply_token: str, message: Message) -> bool:
        try:
            with ApiClient(self._config) as api_client:
                MessagingApi(api_client).reply_message(
                    ReplyMessageRequest(
                        reply_token=reply_token,
                        messages=[message],
                    )
                )
            return True
        except Exception as exc:
            logger.error("LINE reply failed: %s", type(exc).__name__)
            return False
