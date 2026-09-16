<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Mail; // تم استدعاء الكلاس بنجاح

class LegalPagesController extends Controller
{
    // 1. صفحة من نحن
    public function about()
    {
        return Inertia::render('Legal/About');
    }

    // 2. صفحة اتصل بنا (الدالة التي تعرض الصفحة للزائر)
    public function contact()
    {
        return Inertia::render('Legal/Contact');
    }

  // معالجة نموذج اتصل بنا
    public function submitContact(Request $request)
    {
        // 1. التحقق من البيانات
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|min:10',
        ]);

        // 2. إرسال الإيميل الفعلي عبر Resend API
        try {
            $body = "لديك رسالة جديدة من منصة Aql Crypto:\n\n"
                  . "اسم الزائر: {$validated['name']}\n"
                  . "البريد الإلكتروني: {$validated['email']}\n"
                  . "الموضوع: {$validated['subject']}\n\n"
                  . "نص الرسالة:\n{$validated['message']}";

            Mail::raw($body, function ($mail) use ($validated) {
                $mail->to('cryptohubadmin665@gmail.com') // البريد الذي سيستقبل الرسائل
                     ->replyTo($validated['email'], $validated['name']) // للرد المباشر على الزائر
                     ->subject('رسالة جديدة - ' . $validated['subject']);
            });

            // 3. إرجاع رسالة النجاح
            return back()->with('success', 'تم إرسال رسالتك بنجاح. | Your message has been sent successfully.');
            
        } catch (\Exception $e) {
            // في حال فشل الإرسال نرجع رسالة خطأ
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