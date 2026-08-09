<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class LegalPagesController extends Controller
{
    // 1. صفحة من نحن
    public function about()
    {
        return Inertia::render('Legal/About');
    }

    // 2. صفحة اتصل بنا
    public function contact()
    {
        return Inertia::render('Legal/Contact');
    }

    // معالجة نموذج اتصل بنا
    public function submitContact(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|min:10',
        ]);

        // سيتم إضافة كود الإرسال إلى support@aqlcrypto.com لاحقاً
        return back()->with('success', 'تم إرسال رسالتك بنجاح. | Your message has been sent successfully.');
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