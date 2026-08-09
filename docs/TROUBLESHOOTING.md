# Royal Hermes Exhibitions — Troubleshooting "0 exhibitions saved/updated" on XAMPP

If "Run Scrape Now" reports 0 exhibitions, go to **Exhibitions → Settings** and
read the red error box — it now tells you exactly what failed. Below are the
fixes for the most common XAMPP causes, matched to that error message.

---

## Error mentions: SSL, cURL error 60, "certificate", or "unable to get local issuer certificate"

This is the #1 cause on XAMPP. Your local PHP doesn't have a trusted CA
certificate bundle, so it refuses the HTTPS connection to eventseye.com.

**Fix:**
1. Download the CA bundle: https://curl.se/ca/cacert.pem
2. Save it somewhere permanent, e.g. `C:\xampp\php\cacert.pem`
3. Open `C:\xampp\php\php.ini`
4. Find the line `;curl.cainfo=` and change it to:
   ```
   curl.cainfo="C:\xampp\php\cacert.pem"
   ```
   (remove the leading `;`)
5. Also set, just below it:
   ```
   openssl.cafile="C:\xampp\php\cacert.pem"
   ```
6. Restart Apache from the XAMPP Control Panel
7. Go back to **Exhibitions → Settings** and click **Run Scrape Now** again

The plugin already sends `sslverify => false` as a safety net, but some
XAMPP/Windows setups still block the handshake before that setting applies —
the cacert.pem fix above resolves it at the root.

---

## Error mentions: "Could not resolve host" or "Connection timed out"

Your XAMPP machine itself has no internet access, or a firewall/antivirus is
blocking PHP from making outbound connections.

**Fix:**
- Confirm you can open https://www.eventseye.com in a normal browser on the
  same machine
- Temporarily disable Windows Firewall / antivirus and retry, to confirm
  that's the cause
- If confirmed, add an outbound rule allowing `php.exe` (inside
  `C:\xampp\php\`) and `httpd.exe` (inside `C:\xampp\apache\bin\`) through
  your firewall/antivirus

---

## Error mentions: "UNEXPECTED CONTENT" or shows a snippet that looks like an error page

This means a request *did* go through, but EventsEye (or something between
you and it, like a corporate proxy) returned something other than the real
page — possibly a CAPTCHA, a blocked-IP notice, or a proxy login page.

**Fix:**
- Open the same URL shown in the error message directly in your browser
- If you see a CAPTCHA or block page there too, EventsEye may be temporarily
  rate-limiting your IP — wait a few hours and try again
- If your browser shows the real page fine, but PHP doesn't, you likely have
  a proxy configured system-wide that PHP isn't using — check
  `C:\xampp\php\php.ini` for any `http.proxy` settings

---

## Error mentions HTTP status 403 or 503

EventsEye is actively blocking the request (bot detection), similar to what
we saw with 10times.com earlier. If this happens consistently even after the
SSL fix above, the realistic options are:
- Reduce frequency further (already only once/day)
- Try again after some hours — many sites lift temporary blocks
- Switch primary data source to ExpoCart or Display Wizard (confirmed not to
  block automated requests as of testing)

---

## Still stuck?

Copy the exact text from the red error box on the Settings page and send it
back for further diagnosis — the message now includes the failing URL, the
HTTP status (if any), and a snippet of whatever was actually returned.
