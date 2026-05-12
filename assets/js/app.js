/* =============================================
   HRMS – Main JavaScript (app.js)
   =============================================
   This file is loaded on every page via includes/footer.php.
   It provides small interactive features used across the site.

   Contents:
     1. Live clock — updates the time display every second
     2. Sidebar toggle — hamburger menu on mobile
     3. Auto-dismiss alerts — flash messages close after 4 seconds
     4. Password toggle — show/hide password in input fields
     5. applyFilter() — used by filter bars to reload the page with new query params
*/


/* ── 1. Live Clock ─────────────────────────────────────────────
   Any element with class "live-clock" shows the current time HH:MM:SS.
   Any element with class "live-date" shows today's date.
   The clock widget on dashboards uses these classes.
*/
function updateClock() {
    var now      = new Date();
    var timeStr  = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    var dateStr  = now.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });

    // Update every element that displays the time
    document.querySelectorAll('.live-clock').forEach(function(el) {
        el.textContent = timeStr;
    });

    // Update every element that displays the date
    document.querySelectorAll('.live-date').forEach(function(el) {
        el.textContent = dateStr;
    });
}
// Run immediately so there's no blank moment, then repeat every 1000ms (1 second)
updateClock();
setInterval(updateClock, 1000);


/* ── 2. Sidebar Toggle + Auto-dismiss alerts ──────────────────
   Run after the DOM is fully loaded.
*/
document.addEventListener('DOMContentLoaded', function() {

    /* Sidebar — the hamburger button (#sidebarToggle) opens/closes
       the sidebar (#sidebar) on mobile screens.
       Clicking anywhere outside the sidebar closes it too. */
    var toggleBtn = document.getElementById('sidebarToggle');
    var sidebar   = document.getElementById('sidebar');

    if (toggleBtn && sidebar) {
        // Toggle open/close when the hamburger is clicked
        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('open');
        });

        // Close the sidebar when clicking anywhere outside it
        document.addEventListener('click', function(e) {
            if (sidebar.classList.contains('open')
                && !sidebar.contains(e.target)
                && e.target !== toggleBtn) {
                sidebar.classList.remove('open');
            }
        });
    }

    /* Auto-dismiss flash alerts — any Bootstrap alert with the
       class "auto-dismiss" will close itself after 4 seconds.
       This is added in Models.php showFlash(). */
    setTimeout(function() {
        document.querySelectorAll('.alert.auto-dismiss').forEach(function(el) {
            var bsAlert = bootstrap.Alert.getOrCreateInstance(el);
            if (bsAlert) bsAlert.close();
        });
    }, 4000); // 4000 milliseconds = 4 seconds

});


/* ── 3. Password Show/Hide Toggle ─────────────────────────────
   Called by the eye icon button next to password fields.
   Switches the input type between 'password' (hidden) and 'text' (visible).

   @param inputId  The id of the <input type="password"> element
   @param iconId   The id of the <i> Bootstrap icon element
*/
function togglePassword(inputId, iconId) {
    var input = document.getElementById(inputId);
    var icon  = document.getElementById(iconId);

    if (!input) return;

    if (input.type === 'password') {
        input.type = 'text';             // show the password
        if (icon) icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';         // hide the password
        if (icon) icon.className = 'bi bi-eye';
    }
}


/* ── 4. Apply Filter ──────────────────────────────────────────
   Used by search/filter bars on list pages (employees, tasks, attendance, etc.).
   Adds the filter values to the URL query string and reloads the page.
   Automatically removes the 'page' param so you go back to page 1.

   @param params  Object with key/value pairs e.g. { search: 'John', role: 'employee' }

   Example usage in HTML:
     <button onclick="applyFilter({ search: document.getElementById('s').value })">
       Filter
     </button>
*/
function applyFilter(params) {
    var url = new URL(window.location.href);

    // Set or remove each parameter
    Object.keys(params).forEach(function(key) {
        if (params[key]) {
            url.searchParams.set(key, params[key]);   // add/update the param
        } else {
            url.searchParams.delete(key);             // remove it if empty
        }
    });

    // Always go back to page 1 when a filter changes
    url.searchParams.delete('page');

    // Navigate to the new URL
    window.location.href = url.toString();
}
