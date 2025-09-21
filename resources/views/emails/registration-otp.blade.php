@component('mail::message')
# Xác nhận đăng ký

@component('mail::panel')
<div style="text-align:center">
    <div style="font-size:28px;font-weight:700;letter-spacing:8px">{{ $otp }}</div>
    <div style="margin-top:8px;color:#6b7280">Mã OTP hết hạn sau 5 phút</div>
    <div style="margin-top:16px">
        @component('mail::button', ['url' => config('app.url')])
        Mở ứng dụng
        @endcomponent
    </div>
</div>
@endcomponent

Trân trọng,<br>
{{ config('app.name') }}
@endcomponent