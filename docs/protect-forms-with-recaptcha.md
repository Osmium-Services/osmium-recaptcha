---
title: Protect your forms with Google reCAPTCHA
tags: recaptcha, google, captcha, spam, bots, score, forms
page: admin/settings/recaptcha
---

**Google reCAPTCHA v3** scores each form submission, invisibly, on how likely it is to be a human.

## Set it up

1. In Google Cloud, create a reCAPTCHA key and copy its **Site Key** and **Secret Key**.
2. Open **Google reCAPTCHA v3 Settings**, enter both keys and tick **Enabled**.
3. Set the **Score Threshold**, from 0.0 (likely a bot) to 1.0 (likely a human). Submissions scoring below it are refused. Google recommends starting at 0.5.
4. Press **Save Settings**.

Ticking Enabled only loads the reCAPTCHA script. A form is protected only if your site's design sends the token and checks it, so ask your developer to wire it into each form.

Google's free tier is 10,000 checks a month and needs a Google Cloud project with billing enabled. Cloudflare Turnstile is a free, unlimited alternative, and you only need one of them.
