import { Head, Link } from '@inertiajs/react';
import {
    ArrowRight,
    BarChart3,
    BookOpen,
    BookOpenCheck,
    Bot,
    CheckCircle2,
    Database,
    FileSpreadsheet,
    HelpCircle,
    Info,
    Package,
    ToggleRight,
} from 'lucide-react';
import { useState } from 'react';
import AdminLayout from '@/components/AdminLayout';
import { routes } from '@/lib/routes';

type Language = 'th' | 'en';

const resourceDefinitions = [
    { key: 'packages', icon: Package, href: routes.packages.create },
    { key: 'faqs', icon: HelpCircle, href: routes.faqs.create },
    { key: 'knowledge', icon: BookOpen, href: routes.knowledge.create },
] as const;

const content = {
    th: {
        pageTitle: 'คู่มือการใช้งาน',
        languageLabel: 'เลือกภาษาคู่มือ',
        introTitle: 'เพิ่มข้อมูลให้ถูกที่ แล้วควบคุมสิ่งที่ AI ใช้ตอบ',
        introBody: 'AI Knowledge เก็บข้อมูลของธุรกิจไว้เป็นหมวดหมู่ เมื่อเปิดสถานะพร้อมใช้งาน ข้อมูลจะถูกเปิดให้ Knowledge API เพื่อให้ LINE Bot นำไปประกอบคำตอบ',
        important: 'การบันทึกข้อมูลไม่ได้ฝึกโมเดล AI แบบถาวร แต่เป็นการอัปเดตแหล่งข้อมูลที่ระบบเรียกใช้ขณะตอบแต่ละครั้ง',
        chooseTitle: 'ควรเพิ่มข้อมูลตรงไหน',
        chooseBody: 'เลือกประเภทตามลักษณะของคำตอบที่ต้องการให้ลูกค้าได้รับ',
        openForm: 'เปิดฟอร์มเพิ่มข้อมูล',
        resources: {
            packages: {
                title: 'แพ็กเกจ',
                description: 'ใช้สำหรับชื่อบริการ ราคาโปรโมชัน ระยะเวลา เงื่อนไข และคำค้น เช่น ลูกค้าถามว่า “ราคาเท่าไร” หรือ “มีแพ็กเกจอะไรบ้าง”',
                example: 'ตัวอย่าง: โปรแกรมดูแลผิว ราคา 1,500 บาท ระยะเวลา 45 นาที',
            },
            faqs: {
                title: 'คำถามพบบ่อย',
                description: 'ใช้กับคำถามที่มีคำตอบชัดเจนและถูกถามซ้ำ เช่น เวลาทำการ สถานที่ เงื่อนไขบริการ หรือการรับประกัน',
                example: 'ตัวอย่าง: ต้องจองล่วงหน้าหรือไม่ → กรุณาจองล่วงหน้า 1 วัน',
            },
            knowledge: {
                title: 'คลังความรู้',
                description: 'ใช้กับข้อมูลทั่วไป นโยบาย ขั้นตอน ที่อยู่ แผนที่ เบอร์ติดต่อ เวลาทำการ และเรื่องที่ไม่ใช่แพ็กเกจหรือ FAQ',
                example: 'ตัวอย่าง: ที่อยู่ ช่องทางติดต่อ วิธีเดินทาง และนโยบายของธุรกิจ',
            },
        },
        flowTitle: 'ข้อมูลเดินทางอย่างไร',
        flowBody: 'ลำดับตั้งแต่ผู้ดูแลเพิ่มข้อมูลจนถึงการตรวจคำตอบของ Bot',
        steps: [
            { title: 'เพิ่มข้อมูล', body: 'แพ็กเกจและโปรโมชันเพิ่มผ่านฟอร์มหรือนำเข้า Excel/CSV ได้ ส่วน FAQ และคลังความรู้ให้กรอกผ่านหน้าเว็บทีละรายการ' },
            { title: 'ตรวจและเปิดสถานะ', body: 'ตรวจข้อความ ราคา และเงื่อนไข จากนั้นเปิดใช้งาน รายการแพ็กเกจต้องเปิด “เผยแพร่” เพิ่มอีกหนึ่งสถานะ' },
            { title: 'เปิดให้ Bot อ่าน', body: 'รายการที่ผ่านสถานะจะถูกเก็บในฐานข้อมูลและเปิดผ่าน Knowledge API เพื่อใช้ค้นหาข้อมูลที่เกี่ยวข้อง' },
            { title: 'ตรวจคำตอบ', body: 'กลับมาที่แดชบอร์ดเพื่อดูว่าลูกค้าถามอะไร Bot ตอบอะไร และตอบสำเร็จหรือไม่' },
        ],
        statusTitle: 'สถานะที่ต้องเข้าใจ',
        activeTitle: 'ใช้งาน (Active)',
        activeBody: 'ใช้กับแพ็กเกจ FAQ และคลังความรู้ เมื่อปิดสถานะ รายการยังถูกเก็บไว้แต่จะไม่ถูกส่งออกผ่าน Knowledge API',
        publishedTitle: 'เผยแพร่ (Published)',
        publishedBody: 'ใช้กับแพ็กเกจเท่านั้น ต้องเปิดทั้ง “ใช้งาน” และ “เผยแพร่” รวมถึงอยู่ในช่วงวันที่มีผล จึงจะพร้อมให้ Bot ใช้ตอบ',
        excelTitle: 'เมื่อมีข้อมูลจำนวนมาก',
        excelBody: 'นำเข้าได้เฉพาะแพ็กเกจและโปรโมชัน ไปที่หน้า นำเข้าแพ็กเกจ ดาวน์โหลดไฟล์ตัวอย่าง แล้วอัปโหลดเพื่อตรวจสอบก่อน ระบบจะข้าม code ซ้ำ ไม่เขียนทับข้อมูลเดิม และเพิ่มรายการใหม่เป็นฉบับร่างจนกว่าจะเปิดเผยแพร่',
        goImport: 'ไปหน้านำเข้าแพ็กเกจ',
        storageTitle: 'ข้อมูลถูกเก็บไว้ที่ไหนและไปต่ออย่างไร',
        storageBody: 'ทุกข้อมูลอยู่ในฐานข้อมูลของ AI Knowledge ไม่ได้ถูกเก็บไว้ในไฟล์ Excel หลัง Import และไม่ถูกส่งไปฝึกโมเดลถาวร',
        storageHeaders: ['ข้อมูล', 'เก็บในระบบ', 'พร้อมให้ Bot ใช้เมื่อ', 'นำไปใช้ตอบ'],
        storageRows: [
            ['แพ็กเกจ', 'รายการแพ็กเกจ', 'ใช้งาน + เผยแพร่ + อยู่ในช่วงวันที่มีผล', 'บริการ ราคา โปรโมชัน และเงื่อนไข'],
            ['FAQ', 'รายการคำถามพบบ่อย', 'เปิดใช้งาน', 'คำถามที่ถูกถามซ้ำและมีคำตอบแน่นอน'],
            ['คลังความรู้', 'รายการความรู้', 'เปิดใช้งาน', 'ที่อยู่ ติดต่อ ขั้นตอน นโยบาย และข้อมูลทั่วไป'],
            ['ประวัติการตอบ', 'Analytics', 'บันทึกอัตโนมัติหลัง Bot ตอบ', 'ตรวจสอบคำถาม คำตอบ ประเภท และสถานะ'],
        ],
        checklistTitle: 'ตรวจให้ครบก่อนเปิดใช้งานจริง',
        checklist: [
            'ลบหรือปิดข้อมูลจำลองที่ไม่ต้องการให้ลูกค้าเห็น',
            'ตรวจราคา วันที่มีผล เงื่อนไข เบอร์ติดต่อ และเวลาทำการ',
            'เปิดสถานะใช้งาน และเปิดเผยแพร่สำหรับแพ็กเกจ',
            'ทดลองถามผ่าน LINE แล้วตรวจคำตอบที่หน้าแดชบอร์ด',
        ],
        viewDashboard: 'เปิดแดชบอร์ด',
    },
    en: {
        pageTitle: 'User Guide',
        languageLabel: 'Select guide language',
        introTitle: 'Add information in the right place and control what AI can answer',
        introBody: 'AI Knowledge stores business information by type. Once an item is ready and enabled, it is exposed through the Knowledge API for the LINE Bot to use when composing an answer.',
        important: 'Saving information does not permanently train the AI model. It updates the data source retrieved while each answer is being prepared.',
        chooseTitle: 'Where should I add information?',
        chooseBody: 'Choose the section based on the kind of answer customers should receive.',
        openForm: 'Open the add form',
        resources: {
            packages: {
                title: 'Packages',
                description: 'Use for service names, prices, promotions, duration, terms, and search keywords—for example, “How much is it?” or “Which packages are available?”',
                example: 'Example: Skin care program, THB 1,500, approximately 45 minutes',
            },
            faqs: {
                title: 'Frequently asked questions',
                description: 'Use for recurring questions with a clear answer, such as booking, payment, rescheduling, or preparation.',
                example: 'Example: Is advance booking required? → Please book one day ahead.',
            },
            knowledge: {
                title: 'Knowledge base',
                description: 'Use for general information, policies, procedures, address, map, contact details, opening hours, and anything that is not a package or FAQ.',
                example: 'Example: Address, contact channels, directions, and business policies',
            },
        },
        flowTitle: 'How information moves through the system',
        flowBody: 'The sequence from adding information to reviewing the Bot’s answer.',
        steps: [
            { title: 'Add information', body: 'Packages and promotions can use a form or Excel/CSV import. FAQs and knowledge entries are added individually through their web forms.' },
            { title: 'Review and enable', body: 'Check the wording, price, and terms. Enable the record; packages must also be marked as Published.' },
            { title: 'Make it available to the Bot', body: 'Eligible records are stored in the database and exposed through the Knowledge API for relevant retrieval.' },
            { title: 'Review answers', body: 'Return to the dashboard to see what customers asked, what the Bot answered, and whether delivery succeeded.' },
        ],
        statusTitle: 'Statuses you should understand',
        activeTitle: 'Active',
        activeBody: 'Used by packages, FAQs, and knowledge entries. A disabled record remains stored but is not returned by the Knowledge API.',
        publishedTitle: 'Published',
        publishedBody: 'Used only by packages. A package must be Active, Published, and within its effective date range before it is available to the Bot.',
        excelTitle: 'When you have many records',
        excelBody: 'Import is available only for packages and promotions. Upload a sample-format file for preview first. Duplicate codes are skipped without overwriting existing data, and new rows are saved as unpublished drafts until reviewed.',
        goImport: 'Open package import',
        storageTitle: 'Where information is stored and what happens next',
        storageBody: 'All information is stored in the AI Knowledge database. It does not remain only in the Excel file after import and is not used to permanently train the model.',
        storageHeaders: ['Information', 'Stored as', 'Available to the Bot when', 'Used to answer'],
        storageRows: [
            ['Packages', 'Package records', 'Active + Published + within effective dates', 'Services, prices, promotions, and terms'],
            ['FAQs', 'FAQ records', 'Active', 'Recurring questions with a defined answer'],
            ['Knowledge', 'Knowledge entries', 'Active', 'Address, contact, procedures, policies, and general facts'],
            ['Answer history', 'Analytics', 'Recorded automatically after a Bot reply', 'Review questions, answers, response types, and status'],
        ],
        checklistTitle: 'Checklist before going live',
        checklist: [
            'Remove or disable mock data that customers should not see.',
            'Verify prices, effective dates, terms, contact details, and opening hours.',
            'Enable records and mark packages as Published.',
            'Send test questions through LINE and review the answers on the dashboard.',
        ],
        viewDashboard: 'Open dashboard',
    },
} as const;

export default function Guide() {
    const [language, setLanguage] = useState<Language>('th');
    const t = content[language];

    const languageSwitcher = (
        <div className="inline-flex rounded-lg border border-slate-300 bg-white p-1" role="group" aria-label={t.languageLabel}>
            {(['th', 'en'] as const).map((value) => (
                <button
                    key={value}
                    type="button"
                    aria-pressed={language === value}
                    onClick={() => setLanguage(value)}
                    className={`rounded-md px-3 py-1.5 text-sm font-medium transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 ${
                        language === value ? 'bg-blue-700 text-white' : 'text-slate-600 hover:bg-slate-100'
                    }`}
                >
                    {value === 'th' ? 'ไทย' : 'English'}
                </button>
            ))}
        </div>
    );

    return (
        <AdminLayout title={t.pageTitle} actions={languageSwitcher}>
            <Head title={t.pageTitle} />

            <section className="rounded-xl bg-slate-900 px-5 py-6 text-white sm:px-7">
                <div className="flex max-w-3xl items-start gap-4">
                    <BookOpenCheck className="mt-1 h-7 w-7 shrink-0 text-blue-400" />
                    <div>
                        <h2 className="text-xl font-semibold text-white">{t.introTitle}</h2>
                        <p className="mt-2 max-w-3xl text-sm leading-6 text-slate-300">{t.introBody}</p>
                        <div className="mt-4 flex items-start gap-2 rounded-lg bg-white/10 px-3.5 py-3 text-sm leading-6 text-slate-200">
                            <Info className="mt-0.5 h-4 w-4 shrink-0 text-blue-300" />
                            <p>{t.important}</p>
                        </div>
                    </div>
                </div>
            </section>

            <section className="mt-8">
                <h2 className="text-lg font-semibold text-slate-900">{t.chooseTitle}</h2>
                <p className="mt-1 text-sm text-slate-600">{t.chooseBody}</p>
                <div className="mt-4 overflow-hidden rounded-xl border border-slate-200 bg-white">
                    {resourceDefinitions.map((resource, index) => {
                        const Icon = resource.icon;
                        const item = t.resources[resource.key];
                        return (
                            <div
                                key={resource.key}
                                className={`grid gap-4 px-5 py-5 lg:grid-cols-[220px_minmax(0,1fr)_auto] lg:items-center ${index > 0 ? 'border-t border-slate-200' : ''}`}
                            >
                                <div className="flex items-center gap-3">
                                    <span className="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 text-blue-700">
                                        <Icon className="h-5 w-5" />
                                    </span>
                                    <h3 className="font-semibold text-slate-900">{item.title}</h3>
                                </div>
                                <div>
                                    <p className="text-sm leading-6 text-slate-700">{item.description}</p>
                                    <p className="mt-1 text-xs leading-5 text-slate-500">{item.example}</p>
                                </div>
                                <Link
                                    href={resource.href}
                                    className="inline-flex items-center gap-1.5 text-sm font-medium text-blue-700 hover:text-blue-800 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"
                                >
                                    {t.openForm}
                                    <ArrowRight className="h-4 w-4" />
                                </Link>
                            </div>
                        );
                    })}
                </div>
            </section>

            <section className="mt-8 rounded-xl border border-slate-200 bg-white px-5 py-6 sm:px-6">
                <h2 className="text-lg font-semibold text-slate-900">{t.flowTitle}</h2>
                <p className="mt-1 text-sm text-slate-600">{t.flowBody}</p>
                <ol className="mt-5 grid gap-5 md:grid-cols-4">
                    {t.steps.map((step, index) => (
                        <li key={step.title} className="relative">
                            <div className="flex items-center gap-3">
                                <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-700 text-sm font-semibold text-white">
                                    {index + 1}
                                </span>
                                <h3 className="font-semibold text-slate-900">{step.title}</h3>
                            </div>
                            <p className="mt-2 text-sm leading-6 text-slate-600">{step.body}</p>
                        </li>
                    ))}
                </ol>
            </section>

            <section className="mt-8">
                <h2 className="text-lg font-semibold text-slate-900">{t.statusTitle}</h2>
                <div className="mt-4 grid gap-4 md:grid-cols-2">
                    <div className="rounded-xl border border-slate-200 bg-white p-5">
                        <div className="flex items-center gap-2 text-blue-700">
                            <ToggleRight className="h-5 w-5" />
                            <h3 className="font-semibold text-slate-900">{t.activeTitle}</h3>
                        </div>
                        <p className="mt-2 text-sm leading-6 text-slate-600">{t.activeBody}</p>
                    </div>
                    <div className="rounded-xl border border-slate-200 bg-white p-5">
                        <div className="flex items-center gap-2 text-blue-700">
                            <CheckCircle2 className="h-5 w-5" />
                            <h3 className="font-semibold text-slate-900">{t.publishedTitle}</h3>
                        </div>
                        <p className="mt-2 text-sm leading-6 text-slate-600">{t.publishedBody}</p>
                    </div>
                </div>
            </section>

            <section className="mt-8 flex flex-col gap-4 rounded-xl border border-slate-200 bg-white px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div className="flex max-w-3xl items-start gap-3">
                    <FileSpreadsheet className="mt-0.5 h-5 w-5 shrink-0 text-blue-700" />
                    <div>
                        <h2 className="font-semibold text-slate-900">{t.excelTitle}</h2>
                        <p className="mt-1 text-sm leading-6 text-slate-600">{t.excelBody}</p>
                    </div>
                </div>
                <Link
                    href={routes.imports.index}
                    className="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-blue-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"
                >
                    {t.goImport}
                    <ArrowRight className="h-4 w-4" />
                </Link>
            </section>

            <section className="mt-8">
                <div className="flex items-start gap-3">
                    <Database className="mt-0.5 h-5 w-5 shrink-0 text-blue-700" />
                    <div>
                        <h2 className="text-lg font-semibold text-slate-900">{t.storageTitle}</h2>
                        <p className="mt-1 max-w-3xl text-sm leading-6 text-slate-600">{t.storageBody}</p>
                    </div>
                </div>
                <div className="mt-4 overflow-x-auto rounded-xl border border-slate-200 bg-white">
                    <table className="min-w-full text-left text-sm">
                        <thead className="border-b border-slate-200 bg-slate-50">
                            <tr>
                                {t.storageHeaders.map((header) => (
                                    <th key={header} className="whitespace-nowrap px-4 py-3 text-xs font-semibold text-slate-600">{header}</th>
                                ))}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {t.storageRows.map((row) => (
                                <tr key={row[0]} className="align-top hover:bg-slate-50">
                                    {row.map((cell, index) => (
                                        <td key={cell} className={`px-4 py-3 leading-6 ${index === 0 ? 'font-medium text-slate-900' : 'text-slate-600'}`}>{cell}</td>
                                    ))}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </section>

            <section className="mt-8 rounded-xl bg-blue-50 px-5 py-6 sm:px-6">
                <div className="flex items-center gap-2">
                    <Bot className="h-5 w-5 text-blue-800" />
                    <h2 className="font-semibold text-blue-950">{t.checklistTitle}</h2>
                </div>
                <ul className="mt-4 grid gap-3 md:grid-cols-2">
                    {t.checklist.map((item) => (
                        <li key={item} className="flex items-start gap-2 text-sm leading-6 text-blue-950">
                            <CheckCircle2 className="mt-1 h-4 w-4 shrink-0 text-blue-700" />
                            {item}
                        </li>
                    ))}
                </ul>
                <Link
                    href={routes.dashboard}
                    className="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-blue-800 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700"
                >
                    <BarChart3 className="h-4 w-4" />
                    {t.viewDashboard}
                </Link>
            </section>
        </AdminLayout>
    );
}
