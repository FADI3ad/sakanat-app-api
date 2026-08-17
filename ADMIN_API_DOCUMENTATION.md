# 📘 توثيق الـ API الخاصة بالإدارة والخدمات المشتركة - تطبيق سكنات (Sakanat API)

هذا التوثيق الشامل يغطي جميع الـ Endpoints الخاصة بـ **الأدمن (المشرف/الإدارة)**، بالإضافة إلى جميع الـ **Endpoints المشتركة** التي يشاركها الأدمن مع باقي أدوار المستخدمين (الطلاب المقيمين، مالكي السكنات، ومزودي الخدمات) عبر موديولات التطبيق الـ 14.

---

## 🔑 المصادقة والترخيص (Authentication & Headers)

تتطلب جميع الـ Endpoints المحمية إرسال التوكن في الـ Header كالتالي:

```http
Authorization: Bearer {YOUR_SANCTUM_TOKEN}
Accept: application/json
Content-Type: application/json
```

---

## 📋 الفهرس (Table of Contents)

1. [موديول إدارة المستخدمين والمصادقة (Auth & Users)](#1-موديول-إدارة-المستخدمين-والمصادقة-auth--users)
2. [موديول المناطق (Areas)](#2-موديول-المناطق-areas)
3. [موديول أنواع الخدمات (Service Types)](#3-موديول-أنواع-الخدمات-service-types)
4. [موديول الخدمات (Services)](#4-موديول-الخدمات-services)
5. [موديول خدمات التوصيل (Delivery Services)](#5-موديول-خدمات-التوصيل-delivery-services)
6. [موديول تعليقات الخدمات (Service Comments)](#6-موديول-تعليقات-الخدمات-service-comments)
7. [موديول رسائل التواصل (Contact Messages)](#7-موديول-رسائل-التواصل-contact-messages)
8. [موديول المحادثات المباشرة (Direct Messages / Chat)](#8-موديول-المحادثات-المباشرة-direct-messages--chat)
9. [موديول السكنات والعقارات (Properties)](#9-موديول-السكنات-والعقارات-properties)
10. [موديول الغرف (Rooms)](#10-موديول-الغرف-rooms)
11. [موديول الأسرّة (Beds)](#11-موديول-الأسرّة-beds)
12. [موديول فواتير المرافق (Utility Bills)](#12-موديول-فواتير-المرافق-utility-bills)
13. [موديول سجلات الحضور والكيرفيو (Attendance & Curfew)](#13-موديول-سجلات-الحضور-والكيرفيو-attendance--curfew)
14. [موديول بلاغات الغياب والسفر (Absences / Travel Reports)](#14-موديول-بلاغات-الغياب-والسفر-absences--travel-reports)

---

## 1️⃣ موديول إدارة المستخدمين والمصادقة (Auth & Users)

### 🟢 تسجيل الدخول (مشترك)
- **Method**: `POST`
- **URL**: `/api/v1/auth/login`
- **Auth**: Public
- **Request Body**:
```json
{
  "email": "admin@sakanat.com",
  "password": "password123"
}
```
- **Response (200 OK)**:
```json
{
  "success": true,
  "message": "تم تسجيل الدخول بنجاح",
  "data": {
    "user": {
      "id": 1,
      "name": "مدير النظام",
      "type": "admin"
    },
    "token": "1|sanctum_token_here..."
  }
}
```

---

### 🟢 تسجيل الخروج (مشترك)
- **Method**: `POST`
- **URL**: `/api/v1/auth/logout`
- **Auth**: `auth:sanctum`
- **Response (200 OK)**:
```json
{
  "status": true,
  "message": "تم تسجيل الخروج بنجاح"
}
```

---

### 🔴 [Admin] عرض جميع المستخدمين
- **Method**: `GET`
- **URL**: `/api/v1/admin/users`
- **Auth**: `auth:sanctum`, `admin`
- **Query Params**:
  - `type` (optional): `admin` | `resident` | `provider` | `property_owner`
  - `is_blocked` (optional): `0` | `1`
  - `search` (optional): بحث بالاسم أو البريد أو الهاتف
  - `per_page` (optional): عدد العناصر في الصفحة (افتراضي: 15)
- **Response (200 OK)**:
```json
{
  "status": true,
  "message": "تم استرجاع قائمة المستخدمين بنجاح",
  "data": [
    {
      "id": 2,
      "name": "أحمد محمود",
      "email": "ahmed@example.com",
      "phone": "01012345678",
      "type": "resident",
      "is_blocked": false,
      "created_at": "2026-08-01T10:00:00.000000Z"
    }
  ],
  "meta": {
    "total": 1,
    "per_page": 15,
    "current_page": 1,
    "last_page": 1
  }
}
```

---

### 🔴 [Admin] إضافة مستخدم جديد
- **Method**: `POST`
- **URL**: `/api/v1/admin/users`
- **Auth**: `auth:sanctum`, `admin`
- **Request Body**:
```json
{
  "name": "محمد علي",
  "email": "mohamed@example.com",
  "phone": "01123456789",
  "password": "password123",
  "type": "property_owner"
}
```
- **Response (201 Created)**:
```json
{
  "status": true,
  "message": "تم إنشاء المستخدم بنجاح",
  "data": {
    "id": 3,
    "name": "محمد علي",
    "email": "mohamed@example.com",
    "phone": "01123456789",
    "type": "property_owner",
    "is_blocked": false,
    "created_at": "2026-08-17T12:00:00.000000Z"
  }
}
```

---

### 🔴 [Admin] عرض تفاصيل مستخدم
- **Method**: `GET`
- **URL**: `/api/v1/admin/users/{user}`
- **Auth**: `auth:sanctum`, `admin`
- **Response (200 OK)**:
```json
{
  "status": true,
  "message": "تم استرجاع بيانات المستخدم بنجاح",
  "data": {
    "id": 2,
    "name": "أحمد محمود",
    "email": "ahmed@example.com",
    "phone": "01012345678",
    "type": "resident",
    "is_blocked": false,
    "created_at": "2026-08-01T10:00:00.000000Z",
    "residence": {
      "bed_id": 5,
      "room": "غرفة 101",
      "property": "سكن الأمل للطلاب"
    },
    "properties_count": 0
  }
}
```

---

### 🔴 [Admin] تعديل بيانات مستخدم
- **Method**: `PUT`
- **URL**: `/api/v1/admin/users/{user}`
- **Auth**: `auth:sanctum`, `admin`
- **Request Body**:
```json
{
  "name": "أحمد محمود المعدل",
  "email": "ahmed_updated@example.com",
  "phone": "01012345678",
  "type": "resident"
}
```
- **Response (200 OK)**:
```json
{
  "status": true,
  "message": "تم تحديث بيانات المستخدم بنجاح",
  "data": {
    "id": 2,
    "name": "أحمد محمود المعدل",
    "email": "ahmed_updated@example.com",
    "phone": "01012345678",
    "type": "resident",
    "is_blocked": false,
    "updated_at": "2026-08-17T12:05:00.000000Z"
  }
}
```

---

### 🔴 [Admin] حظر / إلغاء حظر مستخدم (Toggle Block)
- **Method**: `PATCH`
- **URL**: `/api/v1/admin/users/{user}/block`
- **Auth**: `auth:sanctum`, `admin`
- **Response (200 OK)**:
```json
{
  "status": true,
  "message": "تم حظر المستخدم بنجاح",
  "data": {
    "id": 2,
    "name": "أحمد محمود",
    "is_blocked": true
  }
}
```

---

### 🔴 [Admin] حذف مستخدم
- **Method**: `DELETE`
- **URL**: `/api/v1/admin/users/{user}`
- **Auth**: `auth:sanctum`, `admin`
- **Response (200 OK)**:
```json
{
  "status": true,
  "message": "تم حذف المستخدم بنجاح"
}
```

---

## 2️⃣ موديول المناطق (Areas)

### 🟢 عرض جميع المناطق (مشترك / عام)
- **Method**: `GET`
- **URL**: `/api/v1/areas`
- **Auth**: Public

### 🟢 عرض تفاصيل منطقة (مشترك / عام)
- **Method**: `GET`
- **URL**: `/api/v1/areas/{area}`
- **Auth**: Public

### 🟢 عرض خدمات منطقة (مشترك / عام)
- **Method**: `GET`
- **URL**: `/api/v1/areas/{area}/services`
- **Auth**: Public

### 🔴 [Admin] إضافة منطقة جديدة
- **Method**: `POST`
- **URL**: `/api/v1/admin/areas`
- **Auth**: `auth:sanctum`, `admin`
- **Request Body**:
```json
{
  "name": "المنطقة الأولى - مدينة نصر"
}
```

### 🔴 [Admin] تعديل منطقة
- **Method**: `PUT`
- **URL**: `/api/v1/admin/areas/{area}`
- **Auth**: `auth:sanctum`, `admin`
- **Request Body**:
```json
{
  "name": "المنطقة الأولى المعدلة"
}
```

### 🔴 [Admin] حذف منطقة
- **Method**: `DELETE`
- **URL**: `/api/v1/admin/areas/{area}`
- **Auth**: `auth:sanctum`, `admin`

---

## 3️⃣ موديول أنواع الخدمات (Service Types)

### 🟢 عرض جميع أنواع الخدمات (مشترك / عام)
- **Method**: `GET`
- **URL**: `/api/v1/types`
- **Auth**: Public

### 🟢 عرض تفاصيل نوع خدمة (مشترك / عام)
- **Method**: `GET`
- **URL**: `/api/v1/types/{type}`
- **Auth**: Public

### 🟢 عرض الخدمات التابعة لنوع محدد (مشترك / عام)
- **Method**: `GET`
- **URL**: `/api/v1/types/{type}/services`
- **Auth**: Public

### 🟢 عرض خدمات التوصيل التابعة لنوع محدد (مشترك / عام)
- **Method**: `GET`
- **URL**: `/api/v1/types/{type}/delivery-services`
- **Auth**: Public

### 🔴 [Admin] إضافة نوع خدمة جديد
- **Method**: `POST`
- **URL**: `/api/v1/admin/types`
- **Auth**: `auth:sanctum`, `admin`
- **Request Body (Form-Data)**:
  - `name`: "صيانة وتكييف"
  - `description`: "خدمات صيانة الأجهزة والتكييفات"
  - `sort_order`: 1
  - `status`: 1
  - `icon` (file, optional): صورة الأيقونة

### 🔴 [Admin] تعديل نوع خدمة
- **Method**: `PUT`
- **URL**: `/api/v1/admin/types/{type}`
- **Auth**: `auth:sanctum`, `admin`

### 🔴 [Admin] حذف نوع خدمة
- **Method**: `DELETE`
- **URL**: `/api/v1/admin/types/{type}`
- **Auth**: `auth:sanctum`, `admin`

---

## 4️⃣ موديول الخدمات (Services)

### 🟢 عرض جميع الخدمات (مشترك / عام)
- **Method**: `GET`
- **URL**: `/api/v1/services`
- **Auth**: Public

### 🟢 عرض تفاصيل خدمة (مشترك / عام)
- **Method**: `GET`
- **URL**: `/api/v1/services/{service}`
- **Auth**: Public

### 🟢 عرض بيانات صاحب الخدمة وخدماته الأخرى (مشترك / عام)
- **Method**: `GET`
- **URL**: `/api/v1/services/{service}/owner`
- **Auth**: Public

### 🔴 [Admin] عرض جميع الخدمات للإشراف
- **Method**: `GET`
- **URL**: `/api/v1/admin/services`
- **Auth**: `auth:sanctum`, `admin`
- **Query Params**:
  - `type_id`: معرف النوع
  - `area_id`: معرف المنطقة
  - `is_available`: `0` | `1`
  - `search`: بحث بالعنوان أو الوصف

### 🔴 [Admin] عرض تفاصيل خدمة للإشراف
- **Method**: `GET`
- **URL**: `/api/v1/admin/services/{service}`
- **Auth**: `auth:sanctum`, `admin`

### 🔴 [Admin] تفعيل / تعليق خدمة (Toggle Availability)
- **Method**: `PATCH`
- **URL**: `/api/v1/admin/services/{service}/toggle`
- **Auth**: `auth:sanctum`, `admin`

### 🔴 [Admin] حذف خدمة مخالفة
- **Method**: `DELETE`
- **URL**: `/api/v1/admin/services/{service}`
- **Auth**: `auth:sanctum`, `admin`

---

## 5️⃣ موديول خدمات التوصيل (Delivery Services)

### 🟢 عرض جميع خدمات التوصيل (مشترك / عام)
- **Method**: `GET`
- **URL**: `/api/v1/delivery-services`
- **Auth**: Public

### 🟢 عرض تفاصيل خدمة توصيل (مشترك / عام)
- **Method**: `GET`
- **URL**: `/api/v1/delivery-services/{deliveryService}`
- **Auth**: Public

### 🔴 [Admin] عرض قائمة خدمات التوصيل بالإدارة
- **Method**: `GET`
- **URL**: `/api/v1/admin/delivery-services`
- **Auth**: `auth:sanctum`, `admin`

### 🔴 [Admin] إضافة خدمة توصيل جديدة
- **Method**: `POST`
- **URL**: `/api/v1/admin/delivery-services`
- **Auth**: `auth:sanctum`, `admin`
- **Request Body**:
```json
{
  "type_id": 1,
  "name": "توصيل السريع - كابتن علي",
  "phone": "01200000000",
  "vehicle_type": "سكوتر",
  "is_available": true
}
```

### 🔴 [Admin] تعديل خدمة توصيل
- **Method**: `PUT`
- **URL**: `/api/v1/admin/delivery-services/{deliveryService}`
- **Auth**: `auth:sanctum`, `admin`

### 🔴 [Admin] حذف خدمة توصيل
- **Method**: `DELETE`
- **URL**: `/api/v1/admin/delivery-services/{deliveryService}`
- **Auth**: `auth:sanctum`, `admin`

---

## 6️⃣ موديول تعليقات الخدمات (Service Comments)

### 🟢 عرض تعليقات خدمة معينة (مشترك)
- **Method**: `GET`
- **URL**: `/api/v1/services/{service}/comments`
- **Auth**: Public (يراها الجميع؛ الأدمن وصاحب الخدمة يريان التعليقات المكتومة أيضاً)

### 🔴 [Admin] عرض جميع التعليقات للرقابة والرقابة على التعليقات
- **Method**: `GET`
- **URL**: `/api/v1/admin/comments`
- **Auth**: `auth:sanctum`, `admin`
- **Query Params**:
  - `is_active`: `0` | `1`
  - `service_id`: معرف الخدمة

### 🔴 [Admin] تفعيل / إخفاء تعليق (Toggle Visibility)
- **Method**: `PATCH`
- **URL**: `/api/v1/admin/comments/{serviceComment}/toggle`
- **Auth**: `auth:sanctum`, `admin`

### 🔴 [Admin] حذف تعليق نهائياً
- **Method**: `DELETE`
- **URL**: `/api/v1/admin/comments/{serviceComment}`
- **Auth**: `auth:sanctum`, `admin`

---

## 7️⃣ موديول رسائل التواصل (Contact Messages)

### 🔴 [Admin] عرض جميع رسائل تواصل المستخدمين
- **Method**: `GET`
- **URL**: `/api/v1/admin/contact`
- **Auth**: `auth:sanctum`, `admin`
- **Query Params**:
  - `status`: `pending` | `read` | `replied`

### 🔴 [Admin] عرض رسالة محددة (يتم تعليمها كـ read تلقائياً)
- **Method**: `GET`
- **URL**: `/api/v1/admin/contact/{contactMessage}`
- **Auth**: `auth:sanctum`, `admin`

### 🔴 [Admin] الرد على رسالة تواصل
- **Method**: `POST`
- **URL**: `/api/v1/admin/contact/{contactMessage}/reply`
- **Auth**: `auth:sanctum`, `admin`
- **Request Body**:
```json
{
  "reply": "أهلاً بك، تم استلام استفسارك وسيتم حل المشكلة فوراً."
}
```

### 🔴 [Admin] حذف رسالة تواصل
- **Method**: `DELETE`
- **URL**: `/api/v1/admin/contact/{contactMessage}`
- **Auth**: `auth:sanctum`, `admin`

---

## 8️⃣ موديول المحادثات المباشرة (Direct Messages / Chat)

### 🟢 عرض المحادثات النشطة (مشترك للأدمن كمستخدم)
- **Method**: `GET`
- **URL**: `/api/v1/messages/chats`
- **Auth**: `auth:sanctum`

### 🟢 عرض سجل محادثة مع مستخدم معين (مشترك للأدمن)
- **Method**: `GET`
- **URL**: `/api/v1/messages/user/{partner}`
- **Auth**: `auth:sanctum`

### 🟢 إرسال رسالة مباشرة (مشترك للأدمن)
- **Method**: `POST`
- **URL**: `/api/v1/messages`
- **Auth**: `auth:sanctum`

### 🔴 [Admin] الرقابة على جميع المحادثات والرسائل في النظام
- **Method**: `GET`
- **URL**: `/api/v1/admin/messages`
- **Auth**: `auth:sanctum`, `admin`
- **Query Params**:
  - `sender_id`: معرف المرسل
  - `receiver_id`: معرف المستقبل
  - `search`: بحث في نص الرسالة

### 🔴 [Admin] حذف أي رسالة في المحادثات
- **Method**: `DELETE`
- **URL**: `/api/v1/admin/messages/{message}`
- **Auth**: `auth:sanctum`, `admin`

---

## 9️⃣ موديول السكنات والعقارات (Properties)

### 🟢 عرض المقيمين (الطلاب) في السكن (مشترك بين المالك والأدمن)
- **Method**: `GET`
- **URL**: `/api/v1/properties/{property}/residents`
- **Auth**: `auth:sanctum` (مالك السكن أو الأدمن)

### 🔴 [Admin] عرض جميع السكنات والعقارات في النظام
- **Method**: `GET`
- **URL**: `/api/v1/admin/properties`
- **Auth**: `auth:sanctum`, `admin`
- **Query Params**:
  - `user_id`: فلترة حسب مالك السكن
  - `is_available`: `0` | `1`

### 🔴 [Admin] عرض تفاصيل سكن معين
- **Method**: `GET`
- **URL**: `/api/v1/admin/properties/{property}`
- **Auth**: `auth:sanctum`, `admin`

### 🔴 [Admin] حذف سكن من النظام
- **Method**: `DELETE`
- **URL**: `/api/v1/admin/properties/{property}`
- **Auth**: `auth:sanctum`, `admin`

---

## 🔟 موديول الغرف (Rooms)

*جميع Endpoints الغرف مسموحة لمالك السكن وللأدمن (مشترك)*:

### 🟢 عرض جميع غرف سكن معين (مشترك: مالك / أدمن)
- **Method**: `GET`
- **URL**: `/api/v1/properties/{property}/rooms`
- **Auth**: `auth:sanctum`

### 🟢 إضافة غرفة جديدة للسكن (مشترك: مالك / أدمن)
- **Method**: `POST`
- **URL**: `/api/v1/properties/{property}/rooms`
- **Auth**: `auth:sanctum`
- **Request Body**:
```json
{
  "name": "غرفة 102",
  "description": "غرفة ثنائية مع حمام خاص"
}
```

### 🟢 عرض تفاصيل غرفة مع أسرّتها (مشترك: مالك / أدمن)
- **Method**: `GET`
- **URL**: `/api/v1/properties/{property}/rooms/{room}`
- **Auth**: `auth:sanctum`

### 🟢 تعديل بيانات غرفة (مشترك: مالك / أدمن)
- **Method**: `PUT`
- **URL**: `/api/v1/properties/{property}/rooms/{room}`
- **Auth**: `auth:sanctum`

### 🟢 حذف غرفة (مشترك: مالك / أدمن)
- **Method**: `DELETE`
- **URL**: `/api/v1/properties/{property}/rooms/{room}`
- **Auth**: `auth:sanctum`

---

## 11️⃣ موديول الأسرّة (Beds)

*جميع Endpoints الأسرّة مسموحة لمالك السكن وللأدمن (مشترك)*:

### 🟢 عرض الأسرّة داخل غرفة (مشترك: مالك / أدمن)
- **Method**: `GET`
- **URL**: `/api/v1/rooms/{room}/beds`
- **Auth**: `auth:sanctum`

### 🟢 إضافة سرير جديد لغرفة (مشترك: مالك / أدمن)
- **Method**: `POST`
- **URL**: `/api/v1/rooms/{room}/beds`
- **Auth**: `auth:sanctum`
- **Request Body**:
```json
{
  "occupant_name": "أحمد محمود",
  "phone": "01012345678"
}
```

### 🟢 عرض تفاصيل سرير (مشترك: مالك / أدمن)
- **Method**: `GET`
- **URL**: `/api/v1/rooms/{room}/beds/{bed}`
- **Auth**: `auth:sanctum`

### 🟢 تعديل بيانات السرير / تغيير الساكن (مشترك: مالك / أدمن)
- **Method**: `PUT`
- **URL**: `/api/v1/rooms/{room}/beds/{bed}`
- **Auth**: `auth:sanctum`

### 🟢 حذف سرير من الغرفة (مشترك: مالك / أدمن)
- **Method**: `DELETE`
- **URL**: `/api/v1/rooms/{room}/beds/{bed}`
- **Auth**: `auth:sanctum`

---

## 12️⃣ موديول فواتير المرافق (Utility Bills)

*جميع Endpoints الفواتير مسموحة لمالك السكن وللأدمن (مشترك)*:

### 🟢 عرض فواتير سكن معين (مشترك: مالك / أدمن)
- **Method**: `GET`
- **URL**: `/api/v1/properties/{property}/bills`
- **Auth**: `auth:sanctum`
- **Query Params**:
  - `month`: `2026-08`
  - `type`: `electricity` | `water` | `gas` | `internet` | `other`
  - `is_paid`: `0` | `1`

### 🟢 إضافة فاتورة جديدة للسكن (مشترك: مالك / أدمن)
- **Method**: `POST`
- **URL**: `/api/v1/properties/{property}/bills`
- **Auth**: `auth:sanctum`
- **Request Body**:
```json
{
  "type": "electricity",
  "month": "2026-08",
  "amount": 450.50,
  "notes": "فاتورة الكهرباء لشهر أغسطس"
}
```

### 🟢 عرض تفاصيل فاتورة (مشترك: مالك / أدمن)
- **Method**: `GET`
- **URL**: `/api/v1/properties/{property}/bills/{bill}`
- **Auth**: `auth:sanctum`

### 🟢 تعديل بيانات فاتورة (مشترك: مالك / أدمن)
- **Method**: `PUT`
- **URL**: `/api/v1/properties/{property}/bills/{bill}`
- **Auth**: `auth:sanctum`

### 🟢 تسجيل دفع الفاتورة (مشترك: مالك / أدمن)
- **Method**: `PATCH`
- **URL**: `/api/v1/properties/{property}/bills/{bill}/pay`
- **Auth**: `auth:sanctum`

### 🟢 حذف فاتورة (مشترك: مالك / أدمن)
- **Method**: `DELETE`
- **URL**: `/api/v1/properties/{property}/bills/{bill}`
- **Auth**: `auth:sanctum`

---

## 13️⃣ موديول سجلات الحضور والكيرفيو (Attendance & Curfew)

### 🟢 عرض سجل الحضور لسكن محدد (مشترك: مالك / أدمن)
- **Method**: `GET`
- **URL**: `/api/v1/properties/{property}/attendance`
- **Auth**: `auth:sanctum`
- **Query Params**: `date`, `month`, `status`

### 🟢 سجل الحضور اليومي لسكن (مشترك: مالك / أدمن)
- **Method**: `GET`
- **URL**: `/api/v1/properties/{property}/attendance/daily`
- **Auth**: `auth:sanctum`

### 🟢 سجل الحضور الشهري لسكن (مشترك: مالك / أدمن)
- **Method**: `GET`
- **URL**: `/api/v1/properties/{property}/attendance/monthly`
- **Auth**: `auth:sanctum`

### 🟢 ملخص إحصائيات الحضور لسكن (مشترك: مالك / أدمن)
- **Method**: `GET`
- **URL**: `/api/v1/properties/{property}/attendance/summary`
- **Auth**: `auth:sanctum`

### 🟢 تحديث وقت الكيرفيو للسكن (مشترك: مالك / أدمن)
- **Method**: `PATCH`
- **URL**: `/api/v1/properties/{property}/curfew`
- **Auth**: `auth:sanctum`
- **Request Body**:
```json
{
  "curfew_time": "22:00:00"
}
```

### 🔴 [Admin] نظرة شاملة على جميع سجلات الحضور بالنظام
- **Method**: `GET`
- **URL**: `/api/v1/admin/attendance`
- **Auth**: `auth:sanctum`, `admin`
- **Query Params**:
  - `property_id`: معرف السكن
  - `user_id`: معرف الطالب
  - `date`: تاريخ محدد `2026-08-17`
  - `month`: شهر محدد `2026-08`
  - `status`: `present` | `late` | `absent`

---

## 14️⃣ موديول بلاغات الغياب والسفر (Absences / Travel Reports)

### 🟢 عرض بلاغات غياب سكن معين (مشترك: مالك / أدمن)
- **Method**: `GET`
- **URL**: `/api/v1/properties/{property}/absences`
- **Auth**: `auth:sanctum`
- **Query Params**:
  - `active`: `1` (عرض البلاغات النشطة حالياً فقط)
  - `per_page`: عدد النتائج

### 🔴 [Admin] نظرة شاملة على جميع بلاغات الغياب والسفر بالنظام
- **Method**: `GET`
- **URL**: `/api/v1/admin/absences`
- **Auth**: `auth:sanctum`, `admin`
- **Query Params**:
  - `property_id`: معرف السكن
  - `user_id`: معرف الطالب
  - `active`: `1` (البلاغات النشطة حالياً)
- **Response (200 OK)**:
```json
{
  "status": true,
  "message": "تم استرجاع جميع بلاغات الغياب بنجاح.",
  "data": [
    {
      "id": 1,
      "start_date": "2026-08-15",
      "end_date": "2026-08-20",
      "reason": "سفر عائلي",
      "is_active": true,
      "created_at": "2026-08-14T18:00:00.000000Z",
      "resident": {
        "id": 2,
        "name": "أحمد محمود",
        "email": "ahmed@example.com",
        "phone": "01012345678"
      },
      "property": {
        "id": 1,
        "title": "سكن الأمل للطلاب",
        "city": "القاهرة"
      },
      "bed": {
        "id": 5,
        "occupant_name": "أحمد محمود",
        "room": {
          "id": 2,
          "name": "غرفة 101"
        }
      }
    }
  ],
  "meta": {
    "total": 1,
    "per_page": 15,
    "current_page": 1,
    "last_page": 1
  }
}
```

---

 تم إنشاء التوثيق بنجاح ليكون مرجعاً كاملاً لكافة الـ Admin Endpoints والـ Shared Endpoints في تطبيق سكنات.
