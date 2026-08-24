<?php

return [

    'auth' => [
        'registered' => 'تم التسجيل بنجاح، برجاء تأكيد رمز التحقق المرسل إليك.',
        'login_success' => 'تم تسجيل الدخول بنجاح.',
        'logout_success' => 'تم تسجيل الخروج بنجاح.',
        'token_refreshed' => 'تم تحديث رمز الدخول بنجاح.',
        'me_fetched' => 'تم جلب بيانات المستخدم بنجاح.',
        'otp_sent' => 'تم إرسال رمز التحقق بنجاح.',
        'otp_verified' => 'تم تأكيد رمز التحقق بنجاح.',
        'password_reset_success' => 'تم إعادة تعيين كلمة المرور بنجاح.',
        'invalid_credentials' => 'البريد الإلكتروني/رقم الهاتف أو كلمة المرور غير صحيحة.',
        'account_inactive' => 'حسابك غير مفعّل.',
        'account_blocked' => 'تم حظر حسابك.',
        'account_pending' => 'حسابك بانتظار التحقق، برجاء تأكيد رمز التحقق المرسل إليك.',
        'invalid_otp' => 'رمز التحقق غير صحيح أو منتهي الصلاحية.',
        'invalid_refresh_token' => 'رمز التحديث غير صالح أو منتهي الصلاحية.',
        'user_not_found' => 'لا يوجد حساب بهذا البريد الإلكتروني أو رقم الهاتف.',
    ],

    'wallet' => [
        'created' => 'تم إنشاء المحفظة بنجاح.',
        'updated' => 'تم تحديث المحفظة بنجاح.',
        'deleted' => 'تم حذف المحفظة بنجاح.',
        'fetched' => 'تم جلب المحافظ بنجاح.',
        'has_balance' => 'لا يمكن حذف محفظة برصيد غير صفري.',
        'insufficient_balance' => 'هذه العملية ستؤدي إلى رصيد سالب في المحفظة.',
    ],

    'transaction' => [
        'created' => 'تم إنشاء العملية بنجاح.',
        'updated' => 'تم تحديث العملية بنجاح.',
        'deleted' => 'تم حذف العملية بنجاح.',
        'fetched' => 'تم جلب العمليات بنجاح.',
        'category_type_mismatch' => 'نوع التصنيف المختار لا يطابق نوع العملية.',
        'transfer_linked' => 'هذه العملية جزء من تحويل ولا يمكن تعديلها أو حذفها مباشرة.',
    ],

    'transfer' => [
        'created' => 'تم التحويل بنجاح.',
        'fetched' => 'تم جلب التحويلات بنجاح.',
    ],

    'category' => [
        'fetched' => 'تم جلب التصنيفات بنجاح.',
    ],

    'dashboard' => [
        'summary_fetched' => 'تم جلب ملخص لوحة التحكم بنجاح.',
        'chart_fetched' => 'تم جلب بيانات الرسم البياني بنجاح.',
    ],

    'notification' => [
        'fetched' => 'تم جلب الإشعارات بنجاح.',
        'marked_read' => 'تم تحديد الإشعار كمقروء.',
        'all_marked_read' => 'تم تحديد كل الإشعارات كمقروءة.',
        'transaction_income_title' => 'دخل جديد',
        'transaction_expense_title' => 'مصروف جديد',
        'transaction_body' => ':amount :currency في :wallet',
        'low_balance_title' => 'تنبيه رصيد منخفض',
        'low_balance_body' => 'رصيد محفظة ":wallet" أصبح :balance :currency.',
        'transfer_title' => 'تم التحويل',
        'transfer_body' => ':amount :currency من :from إلى :to',
    ],

    'errors' => [
        'validation_failed' => 'البيانات المدخلة غير صحيحة.',
        'unauthenticated' => 'يجب تسجيل الدخول أولاً.',
        'forbidden' => 'غير مصرح لك بتنفيذ هذا الإجراء.',
        'not_found' => 'العنصر المطلوب غير موجود.',
        'server_error' => 'حدث خطأ ما، برجاء المحاولة مرة أخرى لاحقًا.',
        'too_many_requests' => 'طلبات كثيرة جدًا، برجاء التمهل قليلاً.',
    ],

];
