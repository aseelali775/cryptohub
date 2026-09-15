<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Mail; // تأكد من إضافة هذا السطر

class LegalPagesController extends Controller
{
    // 1. صفحة من نحن
    public function about()
    {
        return Inertia::render('Legal/About');
    }

    // 2. صفحة اتصل بنا
    public function submitContact(Request $request)
    {
        // 1. التحقق من البيانات
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|min:10',
        ]);

        // 2. إرسال الإيميل الفعلي
        try {
            Mail::raw("اسم المرسل: {$validated['name']} \nالبريد: {$validated['email']} \n\nالرسالة: \n{$validated['message']}", function ($mail) use ($validated) {
                // ضع هنا الإيميل الذي تريد استقبال الرسائل عليه
                $mail->to('cryptohubadmin665@gmail.com') 
                     ->subject('رسالة من صفحة اتصل بنا: ' . $validated['subject']);
            });

            // 3. إرجاع رسالة النجاح
            return back()->with('success', 'تم إرسال رسالتك بنجاح. | Your message has been sent successfully.');
            
        } catch (\Exception $e) {
            // في حال فشل الإرسال (مثلاً مشكلة في إعدادات SMTP) نرجع رسالة خطأ
            return back()->withErrors(['email_error' => 'حدث خطأ أثناء الإرسال، يرجى المحاولة لاحقاً.']);
        }
    }

    // 3. صفحة سياسة الخصوصية
    public function privacyPolicy()
    {
        return Inertia::render('Legal/PrivacyPolicy');
    }

    // 4. صفحة شروط الاستخدام
    public function termsOfUse()
    {
        return Inertia::render('Legal/TermsOfUse');
    }

    // 5. صفحة إخلاء المسؤولية
    public function disclaimer()
    {
        return Inertia::render('Legal/Disclaimer');
    }

    // 6. صفحة سياسة التحرير (الإضافة الذهبية)
    public function editorialPolicy()
    {
        return Inertia::render('Legal/EditorialPolicy');
    }
}