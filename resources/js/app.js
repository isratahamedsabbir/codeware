/*
 |  The admin's JavaScript. Loaded by layouts/admin.blade.php, by
 |  components/layouts/media-picker.blade.php, and by nothing else.
 |
 |  Every library below is used by admin views exclusively, which is why they
 |  live behind this entry and not behind a shared one:
 |
 |    sortablejs   drag-to-reorder in the category, CMS, menu and page listers
 |                 and the product form (6 admin views)
 |    echo         a live Echo on the admin and vendor chat screens, and the
 |                 chat listener inline in layouts/admin.blade.php
 |    date-range-picker   the date filter on the reports screen
 |    chunk-upload        resumable uploads, for the file manager
 |    passkeys            window.Passkeys, the WebAuthn ceremonies behind the
 |                        MFA panel (resources/js/passkeys.js)
 |
 |  The storefront chat deliberately has no Echo — ChatWidget.php explains why:
 |  real-time is one-directional, from the customer up to the admin, so a
 |  visitor needs no websocket at all.
 |
 |  This file is not referenced by partials/_head.blade.php, so a public page
 |  ships none of it. See vite.config.js.
 */

import Sortable from 'sortablejs';
window.Sortable = Sortable;

import './echo';
import './date-range-picker';
import './chunk-upload';
import './passkeys';
