@extends('layouts.public-pages')

@section('title', 'Contact Us')

@section('page-css')
<style>
    .contact-page { padding: 8px 0 28px; }
    .contact-heading { text-align: center; margin-bottom: 18px; }
    .contact-heading h1 { margin: 0; color: #34483e; font-size: clamp(30px, 4vw, 38px); font-weight: 600; line-height: 1.2; }
    .contact-heading p { margin: 6px auto 0; max-width: 600px; color: #60776b; font-size: 14px; line-height: 1.6; }
    .contact-card { width: min(600px, 100%); margin: 0 auto; padding: 24px; }
    .contact-field { margin-bottom: 14px; }
    .contact-field label { display: block; margin-bottom: 8px; color: #34483e; font-size: 15px; font-weight: 600; line-height: 1; }
    .contact-field input, .contact-field textarea {
        display: block;
        width: 100%;
        border: 1.5px solid #c9d5cd;
        border-radius: 5px;
        background: #fff;
        color: #34483e;
        padding: 14px 16px;
        font-size: 14px;
        outline: none;
        transition: border-color .2s ease;
    }
    .contact-field input { height: 54px; }
    .contact-field textarea { min-height: 90px; resize: vertical; }
    .contact-field input::placeholder, .contact-field textarea::placeholder { color: #9ca9a0; font-size: 13.5px; }
    .contact-field input:focus, .contact-field textarea:focus { border-color: #176d54; }
    .contact-submit { display: block; width: 100%; height: 54px; margin-top: 16px; border: 0; border-radius: 5px; background: #176d54; color: #fff; font-size: 17px; font-weight: 600; cursor: pointer; transition: background-color .2s ease; }
    .contact-submit:hover { background: #125843; }
    .contact-submit:focus-visible { outline: 2px solid #176d54; outline-offset: 3px; }
    .contact-status { margin: 18px 0 0; color: #60776b; text-align: center; font-size: 13px; line-height: 1.5; }
    .contact-status[hidden] { display: none; }
    @media (max-width: 640px) {
        .contact-page { padding: 8px 0 28px; }
        .contact-heading { margin-bottom: 18px; }
        .contact-card { padding: 20px; }
    }
</style>
@endsection

@section('content')
<div class="public-container contact-page">
    <header class="contact-heading">
        <h1>Contact Us</h1>
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
