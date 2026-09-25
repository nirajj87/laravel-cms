# User manual

This guide is for people who run a workspace. It follows the buttons on screen, in order.

Open the app at [http://localhost:8080](http://localhost:8080).

Local accounts after setup:

| Who | Email | Password |
| --- | --- | --- |
| Super admin | admin@platform.test | password |
| Workspace owner | owner@northwind.test | password |
| Editor | editor@northwind.test | password |
| Meridian Works | owner@meridian.test | password |
| Sable Press | owner@sable.test | password |

Sample public sites:

- [http://localhost:8080/site/northwind](http://localhost:8080/site/northwind)
- [http://localhost:8080/site/meridian](http://localhost:8080/site/meridian)
- [http://localhost:8080/site/sable](http://localhost:8080/site/sable)

On a phone, tap **Menu** at the top to open the sidebar.

---

## 1. Sign in

1. Go to [http://localhost:8080/login](http://localhost:8080/login).
2. Enter the email and password.
3. Click **Sign in**.

A workspace user lands on the workspace **Dashboard**. A super admin lands on the platform **Dashboard**.

To leave, click **Sign out** at the top right.

### Forgot password

1. On the sign-in page, click **Forgot password?**
2. Enter the account email.
3. Click **Email reset link**.
4. Open the link in the email, choose a new password, and click **Update password**.
5. Sign in with the new password.

The page always says a link was sent, even when that email is not registered.

---

## 2. Super admin: create a workspace

Use this only with the super admin account.

1. Sign in as `admin@platform.test`.
2. In the sidebar, click **Tenants**.
3. Click **Add tenant**.
4. Fill in the workspace name, slug, owner name, owner email, and password.
5. Choose which modules this workspace can use.
6. Save the form.

The slug becomes the public address: `/site/{slug}`.

### Open that workspace

1. Click **Tenants**.
2. Click the workspace name.
3. Click **Open workspace**.

An amber bar shows that you are inside that workspace. Click **Exit workspace** to return to the platform.

### Platform backup

1. Click **Backups** in the platform sidebar.
2. Choose **Full database** to dump everything, or pick one workspace.
3. Click **Create backup**.
4. To back up every workspace, click **Backup all workspaces**.
5. When the status is **Completed**, click **Download**. The link expires after 10 minutes.
6. Click **Delete** to remove a file. Confirm when asked.

Full database backups need the queue worker to be running. Refresh the page if the status stays on **Pending**.

---

## 3. Workspace dashboard

After a workspace user signs in, the sidebar lists only the modules that workspace has turned on, and only the ones this user is allowed to open.

The dashboard shows:

- Posts, and how many are published or draft
- Categories
- People
- Feedback
- Storage
- Recent activity

---

## 4. Categories

1. Click **Categories**.
2. Click **Add category**.
3. Enter the name. The slug is created from the name.
4. Optional: choose a parent category, image, description, and SEO title.
5. Set the status to **Active**.
6. Save.

Child categories appear under the parent in the public category dropdown. Example: **AI** → **AI Tools**.

The public address is `/site/{workspace}/category/{slug}`.

---

## 5. Content types and fields

A content type is the shape of a post: Book, AI Tool, Product, or anything you add. Cards and detail pages read these fields. They are not hard-coded.

1. Click **Posts**.
2. Click **Content types**.
3. Click **Add content type**, or click **Fields** on a type that already exists.
4. For each field, set:
   - Label (what people see)
   - Type (text, image, price, rating, button, and so on)
   - Enabled or disabled
   - Required or optional
   - Order
5. Save.

If you turn a field off, it disappears from the post form and from the public card. Existing values are kept, but they are not shown.

Useful fields:

| Field | What it does on the site |
| --- | --- |
| Short description | Text under the title on the card |
| Thumbnail or logo | Card image |
| Author, price, discount, rating | Shown on the card only when the field is enabled and filled in |
| Button | Label, link, style, and whether it opens in a new tab |
| Detail page | When enabled, the card opens `/content/{slug}`. When disabled, the card uses the button link |
| Category | Lets the post be filed under categories |

---

## 6. Add and publish a post

1. Click **Posts**.
2. Click **Add post**.
3. Choose a content type.
4. Enter the title and the fields that are enabled.
5. Choose one or more categories.
6. Set **Status**:
   - **Draft** — saved, not on the public site
   - **Published** — visible now
   - **Scheduled** — visible after the date in **Publish at**
7. In the **SEO** box, optionally set the SEO title, description, keywords, canonical URL, and OG image.
8. Click **Create post**.

To change it later, open the post and click **Save post**. **Preview** shows the page without making it public.

Editors can create and edit. They cannot publish or delete. Owners and managers can publish.

### Where it appears

- Home grid: `/site/{workspace}`
- Category: `/site/{workspace}/category/{slug}`
- Detail: `/site/{workspace}/content/{slug}`
- Type list: `/site/{workspace}/{type-slug}`

On **Posts**, use **View site** to open the public homepage.

---

## 7. Media

1. Click **Media**.
2. Upload an image or file.
3. Use it as a thumbnail, logo, or OG image on a post.

Files must be 20 MB or smaller. Programs and scripts are rejected. The stored filename is generated. It is not the original name.

---

## 8. Pages

Pages are fixed pages such as About and Contact. They are not posts.

1. Click **Pages**.
2. Add a page, or edit **About** or **Contact**.
3. Write the body and set the status to **Published**.
4. Save.

Public address: `/site/{workspace}/page/{slug}`.

The contact form is a separate page: `/site/{workspace}/contact`.

---

## 9. Menu

1. Click **Menu Manager**.
2. Add an item to the header or footer menu.
3. Set the label and where it goes:
   - Home
   - A page
   - A category
   - A content type
   - Categories (jumps to the filter)
   - Login
   - An external http or https URL
4. Set the order.
5. Leave it enabled.
6. Save.

To hide an item, disable it. Disabled items stay in the admin list and disappear from the public site.

---

## 10. Layout

1. Click **Layout Builder**.
2. Each block sits in the header, the main area, or the footer.
3. Set the row, the order inside the row, and how many columns it spans on desktop, tablet, and mobile.
4. Enable or disable the block.
5. Save.

The default homepage is:

- Row 1: logo and menu
- Then a search hero, a category filter, and the content grid
- Footer: description, menu, contact, newsletter, and copyright

The content grid shows 4 columns on a desktop and 16 cards per page. Change the column count and the listing style under **Theme Settings**.

---

## 11. Header and footer

1. Click **Header / Footer**.
2. Turn pieces on or off: logo size, search, login, call-to-action, footer text, contact details, social links, newsletter, copyright.
3. Social links must be normal http or https URLs.
4. Save.

Custom text is cleaned. Scripts are removed.

---

## 12. Theme

1. Click **Theme Settings**.
2. Set colors, font, corner radius, card style, header style, footer style, container width, and grid columns.
3. Choose light, dark, or system.
4. Choose how the list loads more cards:
   - **Pagination**
   - **Load more**
   - **Infinite scroll**
5. Turn hover and fade effects on or off.
6. Save.

Colors must be picked from the color control. Anything that is not a hex color is ignored. Visitors who prefer reduced motion do not get the animations.

---

## 13. Use the public site

1. Open `/site/{workspace}`.
2. Type in **Search content, tools, books...** and click **Search**.
3. Or change **Category**. The address becomes `/site/{workspace}/category/{slug}`.
4. Click a card title to open the detail page, when that content type has a detail page.
5. Click the card button (**Use tool**, **Buy now**, and so on) to follow that item’s link.

Search looks at the title, the category name, and fields marked searchable.

---

## 14. SEO

1. Click **SEO**.
2. Set the site title, meta description, keywords, canonical URL, robots, Open Graph title and description, OG image, and Twitter card.
3. Click **Save SEO**.

Per-post SEO is on the post form, in the **SEO** box. A post value overrides the site default.

Check the results:

- `/site/{workspace}/sitemap.xml`
- `/site/{workspace}/robots.txt`

If robots is `noindex,nofollow`, the robots file tells crawlers not to index the site.

---

## 15. Analytics

1. Click **Analytics**.
2. Tick **Enable Google Analytics**.
3. Enter a Measurement ID in the form `G-XXXXXXXXXX`.
4. Optionally enable Tag Manager (`GTM-XXXX`) or a numeric Meta Pixel ID.
5. Click **Save analytics**.

An ID that does not match the pattern is ignored and is not added to the page.

---

## 16. Email

1. Click **Email**.
2. Enter the SMTP host, port, username, encryption, from name, and from email.
3. Enter the password once. Leave it blank later to keep the saved password. It is never shown again.
4. Edit each template subject and body. Placeholders such as `{{name}}` and `{{email}}` are filled when the mail is sent.
5. Click **Save email**.

These templates cover the contact form, feedback notices, registration, password reset, and general notifications. Mail is sent by the queue worker.

The public contact form is `/site/{workspace}/contact`. The message goes to the contact email in **Header / Footer**, or the workspace email.

---

## 17. Feedback

Visitors can send feedback from a content detail page:

1. Enter name, email, a rating from 1 to 5, and a comment.
2. Click **Send feedback**.

The note stays hidden until you publish it.

As an admin:

1. Click **Feedback**.
2. Change the status to **Approved**, **Rejected**, **Published**, or **Hidden**.
3. **Published** notes show on that content page.
4. Click **Delete** to remove one. Confirm when asked.

Captcha is optional. On the same screen, choose a provider and save a secret before you tick **Require captcha**. Until that is done, turning captcha on blocks new submissions.

---

## 18. Workspace backup

Owners can do this. Tenant admins cannot, unless a custom role is given the backup permissions.

1. Click **Backup**.
2. Tick **Include uploaded files** if the copy should contain media.
3. Click **Create backup**.
4. Wait until the status is **Completed**. The queue worker does the work.
5. Click **Download**. The link lasts 10 minutes and only works while you are signed in with permission.
6. Click **Delete** to remove an old file.

Backup files are encrypted and are not on the public website. The five newest completed backups are kept.

---

## 19. People and permissions

1. Click **Users** to add someone.
2. Set their name, email, password, and role.
3. Click **Roles** to see what a role can do.
4. A custom role can be given individual permissions, such as “edit posts” without “publish posts”.

| Role | Typical access |
| --- | --- |
| Tenant Owner | Everything enabled for the workspace, including backup |
| Tenant Admin | Same, except backup |
| Tenant Manager | Publish content, edit menus and pages, moderate feedback |
| Tenant Editor | Create and edit content. Cannot publish or delete posts |
| Tenant User | View only |

The sidebar hides anything the signed-in person cannot open. The server still blocks the address if they type it.

---

## 20. Workspace settings

1. Click **Settings**.
2. Update the workspace name, contact details, logo, and favicon.
3. Save.

The logo is used in the public header when a logo block is enabled.

---

## A normal publishing day

1. Sign in as the workspace owner.
2. Add any new category.
3. Upload images in **Media**.
4. Click **Posts**, then **Add post**.
5. Fill the fields, choose a category, set **Published**, and click **Create post**.
6. Click **View site**.
7. Search for the title and open the card.
8. If the menu or colors should change, use **Menu Manager** or **Theme Settings**, then reload the public page.
