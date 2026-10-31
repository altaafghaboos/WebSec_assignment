# Bloom Beauty — individual Week 10 module

**Scope:** The landing page shown in the supplied Figma export, plus a working PHP/MySQL services catalogue with a jQuery availability filter. This is **not the entire group's website**. The booking, products and login destinations are explicitly marked as placeholders; they do not save data.

## Run locally on Windows
1. Install XAMPP from the official Apache Friends website. Start **Apache** and **MySQL**.
2. In `http://localhost/phpmyadmin`, use **Import** to import your group's original `beauty_salon_booking_system (2).sql` file. **Important:** The SQL script drops and recreates tables, so do not import it over a database containing work you want to keep.
3. Extract this folder to `C:\xampp\htdocs\BloomBeauty_My_Week10_Part`.
4. Visit `http://localhost/BloomBeauty_My_Week10_Part/index.php`.
5. Click **Explore services and prices** to see services read from MySQL. Test the availability filter.
6. jQuery loads from a CDN and requires an internet connection for filtering.

## Files to explain during presentation
- `index.php`: home page structure based on Figma.
- `assets/style.css`: responsive layout and styling.
- `services.php`: PHP database query, escaped service details, price and duration.
- `config.php`: PDO database connection (local development credentials).
- `assets/app.js`: jQuery filter.
- `assets/*.png`: crops from the Figma PNG you provided, not separate original source assets. For a final-quality site, export the original images individually from Figma.

## Demonstration
- Show home page and Figma resemblance.
- Show services from the existing SQL `services` table, with Rs 900 / Rs 1200 / Rs 650 example prices.
- Filter Available and Unavailable with jQuery.
- Explain that booking and login need team integration and are not implemented here.
- Commit the files to the group repository after agreeing integration paths with teammates.

**Security note:** Database output is escaped with `htmlspecialchars`. No customer input is written to the database in this module. Real booking/login must use prepared statements, secure sessions, authorization and CSRF protections.
