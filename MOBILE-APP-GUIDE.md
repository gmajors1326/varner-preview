# Varner OS — Mobile App Guide

How to install the Varner OS mobile app, log in, and manage who has access.

> **App address:**
> - **Now (testing):** `https://varnerequipdev.wpenginepowered.com/mobile-app/`
> - **At go-live:** this becomes your real address, e.g. `https://varnerequipment.com/mobile-app/`
>
> When the site goes live, everyone deletes the old icon and re-adds it from the new address (one time). Nothing else changes.

---

## PART 1 — FOR THE CREW (installing & logging in)

### Put the app on your phone (one time)

**iPhone / iPad (use Safari):**
1. Open **Safari** and go to the app address above.
2. Tap the **Share** button (the square with an up-arrow).
3. Tap **Add to Home Screen**, then **Add**.
4. You now have a **Varner OS** icon, just like a real app.

**Android (use Chrome):**
1. Open **Chrome** and go to the app address above.
2. Tap the **⋮** menu (top right).
3. Tap **Install app** (or **Add to Home screen**).
4. You now have a **Varner OS** icon.

### Log in (one time)
1. Tap the **Varner OS** icon to open it.
2. Type your **username (or email)** and **password** — the same ones the admin gave you.
3. Tap **Log In**. You're in.

### After that
- Just tap the icon — it **remembers you** and opens right up.
- It stays logged in for about **2 weeks** without asking again.
- After that (or if you tap **Sign Out**), it'll ask for your password one more time.

### Sharing a phone? (borrowed or shop iPad)
- Don't save your password on a phone that isn't yours.
- On the login screen, tap **"Use an access token instead,"** then get a token/QR code from the admin's desktop (the **Mobile Companion** tab) and enter it.

### If something goes wrong
- **"Invalid username or password"** → double-check them; if still stuck, ask the admin to reset your password.
- **"This account is not authorized for the mobile app"** → your account doesn't have the right access level yet. Tell the admin (see Part 2).
- **"Too many login attempts"** → wait 15 minutes and try again.
- **It signed me out** → just log back in with your username and password.

---

## PART 2 — FOR THE ADMIN (managing accounts)

### Every person needs their own account
Each crew member logs in with their **own** WordPress account. This is what lets you see **who** changed what (every save, price change, and publish is logged under their name). Don't share one login between people — if you do, the history shows everyone as the same name.

### Create an account for someone
1. Log into WordPress admin → **Users → Add New**.
2. Fill in their **username** and **email**.
3. **Set the Role to "Editor."** (See the role note below — this matters.)
4. Set a **password** (or have WordPress generate one).
5. Click **Add New User**.
6. **Give them their username + password** (text/tell them directly — see the email note below).

### Role note — IMPORTANT
The mobile app only lets in accounts that are allowed to manage inventory:
- ✅ **Editor** — full inventory access. Use this for crew.
- ✅ **Author** — works, but with some limits (can't see deleted units or settings).
- ❌ **Subscriber** — blocked. The app will say "not authorized."

If someone reports "not authorized," open their user in WordPress and change their **Role to Editor**.

### About emailing passwords
WordPress can email a new user a link to set their own password — **but that needs working email (SMTP), which isn't set up yet.** Until then, **set each password yourself** and hand it to the person directly. Once SMTP is live (a go-live item), new users can get their own setup link automatically.

### Removing or changing access
- **Remove someone:** WordPress admin → **Users** → hover their name → **Delete** (or change their role to **Subscriber** to block the app without deleting them).
- **Reset a password:** open their user → **Set New Password** → share the new one.
- A change takes effect the next time they open the app.

### Seeing who did what (audit log)
Every create, edit, publish, delete, and restore is recorded with the person's name, the time, and the exact changes (e.g. *price: 5,000 → 4,500*). You can review this:
- **Per unit** — open a unit to see its history.
- **Everything** — the admin-only audit log shows all activity across all units.

### Quick setup checklist for go-live
- [ ] Create an **Editor** account for each crew member.
- [ ] Send each person: the app address, their username, their password, and Part 1 of this guide.
- [ ] Confirm each person can install the app and log in.
- [ ] (Later) Once SMTP/email is live, switch new users to email-based password setup.
