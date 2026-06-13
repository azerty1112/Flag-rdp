منصة لإنشاء جلسات RDP متتالية مع مزامنة المجلدات في حساب GitHub الخاص بالمستخدم.

## إعداد المتغيرات (config)
أنشئ ملف `.env` في جذر المشروع وأدخل القيم التالية:

```env
APP_NAME="RDP Orchestrator Pro"
APP_URL="https://your-domain.example"
GITHUB_CLIENT_ID="your_github_client_id"
GITHUB_CLIENT_SECRET="your_github_client_secret"
PLATFORM_GITHUB_TOKEN="your_github_pat_or_empty"
TEMPLATE_REPO_OWNER="your_github_username"
TEMPLATE_REPO_NAME="rdp-template-sync"
DB_PATH="/path/to/database/sessions.db"
SESSION_TIMEOUT=21600
MAX_REPEAT_SESSIONS=10
MAX_FILE_SIZE=524288000
SECRET_KEY="change-me-32-byte-secret"
SESSION_NAME="RDP_ORCH"
```

ملاحظة: هذا المشروع يقرأ القيم من متغيرات البيئة تلقائياً عبر `getenv()`, لذلك يمكن وضعها في `.env` أو في إعدادات الاستضافة.

## خطوات الإعداد
1. إنشاء تطبيق OAuth في GitHub.
2. إضافة `GITHUB_CLIENT_ID` و `GITHUB_CLIENT_SECRET` و `APP_URL` في البيئة.
3. إنشاء مستودع قالب عام `rdp-template-sync` وإضافة workflow المناسب.
4. إضافة `NGROK_TOKEN` و `UPLOAD_SECRET` (وهو `md5(SECRET_KEY)`) إلى المستودع القالب.
5. المستخدم يسجل دخوله.
6. يرفع مجلد ZIP.
7. يحدد عدد الجلسات المتتالية.
8. تبدأ الجلسة الأولى، يتم تحميل المجلد على سطح المكتب.
9. بعد التطوير، تنتهي الجلسة تلقائياً ويتم رفع المجلد المحدث.
10. تبدأ الجلسة التالية تلقائياً مع المجلد المحدث.