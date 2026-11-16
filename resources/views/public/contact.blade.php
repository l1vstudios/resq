@extends('layouts.public-pages')

@section('title', 'Contact Us')

@section('page-css')
<style>
    .contact-page { padding: clamp(54px, 8vw, 104px) 0 96px; }
    .contact-heading { text-align: center; margin-bottom: clamp(40px, 6vw, 70px); }
    .contact-heading .page-title { font-size: clamp(58px, 9vw, 106px); }
    .contact-heading p { margin: 18px auto 0; max-width: 600px; color: #62736a; font-size: 16px; line-height: 1.65; }
    .contact-card { width: min(700px, 100%); margin: 0 auto; padding: clamp(28px, 5vw, 60px); }
    .contact-field { margin-bottom: 26px; }
    .contact-field label { display: block; margin-bottom: 10px; color: #17241f; font-size: 15px; font-weight: 600; }
    .contact-field input, .contact-field textarea {
        display: block;
        width: 100%;
        border: 1px solid #e9eeea;
        border-radius: 8px;
        background: #f3f5f3;
        color: #17241f;
        padding: 16px 18px;
        font-size: 16px;
        outline: none;
        transition: border-color .2s ease, box-shadow .2s ease;
    }
    .contact-field input { min-height: 58px; }
    .contact-field textarea { min-height: 150px; resize: vertical; }
    .contact-field input::placeholder, .contact-field textarea::placeholder { color: #a7b2ab; }
    .contact-field input:focus, .contact-field textarea:focus { border-color: #176d54; box-shadow: 0 0 0 4px rgba(23, 109, 84, .1); }
    .contact-submit { display: block; min-width: 188px; margin: 38px auto 0; padding: 17px 36px; border: 0; border-radius: 999px; background: #111b16; color: #fff; font-size: 16px; font-weight: 700; cursor: pointer; transition: background .2s ease, transform .2s ease; }
    .contact-submit:hover { background: #176d54; transform: translateY(-2px); }
    .contact-submit:focus-visible { outline: 3px solid #58b38d; outline-offset: 3px; }
    .contact-status { margin: 22px 0 0; padding: 14px 18px; border-radius: 8px; background: #e8f4ed; color: #24583e; text-align: center; font-size: 14px; line-height: 1.5; }
    .contact-status[hidden] { display: none; }
    @media (max-width: 640px) {
        .contact-page { padding-bottom: 58px; }
        .contact-heading { margin-bottom: 34px; }
        .contact-field { margin-bottom: 20px; }
        .contact-card { border-radius: 16px; }
        .contact-submit { width: 100%; }
    }
</style>
@endsection

@section('content')
<div class="public-container contact-page">
    <header class="contact-heading">
        <p class="page-eyebrow">PT Sentinal ResQ Tech</p>
        <h1 class="page-title">Contact Us</h1>
        <p>Ceritakan kebutuhan atau pertanyaan Anda melalui formulir berikut.</p>
    </header>

    <div class="public-card contact-card">
        <form id="contact-form" novalidate>
            <div class="contact-field">
                <label for="contact-name">Name <span aria-hidden="true">*</span></label>
                <input id="contact-name" name="name" type="text" placeholder="Your name" autocomplete="given-name" required>
            </div>
            <div class="contact-field">
                <label for="contact-last-name">Last name</label>
                <input id="contact-last-name" name="last_name" type="text" placeholder="Your last name" autocomplete="family-name">
            </div>
            <div class="contact-field">
                <label for="contact-email">Your email <span aria-hidden="true">*</span></label>
                <input id="contact-email" name="email" type="email" placeholder="Your email address" autocomplete="email" required>
            </div>
            <div class="contact-field">
                <label for="contact-message">Message <span aria-hidden="true">*</span></label>
                <textarea id="contact-message" name="message" placeholder="Enter your message" required></textarea>
            </div>
            <button class="contact-submit" type="button" id="contact-submit">Submit</button>
            <p class="contact-status" id="contact-status" role="status" hidden>Formulir ini masih berupa tampilan. Pengiriman pesan belum tersedia.</p>
        </form>
    </div>
</div>
@endsection

@section('page-script')
<script>
    document.getElementById('contact-submit').addEventListener('click', function () {
        document.getElementById('contact-status').hidden = false;
    });
</script>
@endsection
