# Changelog

All notable changes to this project will be documented in this file.

## [1.3.0](https://github.com/oskratch/prestashop-module-pickupscheduler/compare/pickupscheduler-v1.2.1...v1.3.0) (2026-10-12)


### Features

* redesigned configuration page: a single form with status cards (upcoming pickups, active days, free slots), a weekly schedule with on/off switches and a live preview of the slots per day, and validation of every field before saving
* the "Store pickups" list can switch between upcoming pickups (soonest first) and the full history, and shows the customer's email, order reference and coloured order status
* the order confirmation email is sent to the order's customer in the order's language when a template exists for it (Spanish otherwise)


### Bug Fixes

* booking a slot crashed on PrestaShop 9 (`ajaxDie()` no longer exists)
* the pickup calendar never appeared with the Hummingbird theme (PrestaShop 9's default); the checkout script now works with Classic and Hummingbird
* editing the pickup carrier in PrestaShop (which gives it a new id) broke the checkout calendar and hid the pickup info of older orders; carriers are now matched by reference
* two customers could book the same slot at the same time; the slot is now claimed atomically
* anonymous requests or unknown slot ids could leave slots blocked forever; booking now requires a customer and the shop token, and the upgrade releases slots already blocked this way
* customers could book slots inside the preparation period or already started by calling the endpoint directly
* clicking the slot you already hold said it had been taken by another customer
* after choosing a second slot, the "Confirm" button could use the first slot's expiry time and wrongly report it as expired
* the expiry countdown depended on the customer's clock and time zone, and failed on Safari
* expired reservations were compared with MySQL's clock instead of PHP's, releasing them early or late when the two time zones differ
* order pages showed PHP warnings when a pickup order had no slot
* the calendar's table styles also removed the padding of every other table on the checkout page
* the page no longer jumps to the selected slot every time the calendar is redrawn


### Miscellaneous Chores

* release zip is built and attached automatically by GitHub Actions; PHP syntax is checked on every push
* `index.php` guards in every folder, as PrestaShop requires
* versions are now managed manually (release-please removed)

## [1.2.1](https://github.com/oskratch/prestashop-module-pickupscheduler/compare/pickupscheduler-v1.2.0...pickupscheduler-v1.2.1) (2026-07-01)


### Bug Fixes

* declare compatibility with PrestaShop 9 ([093a948](https://github.com/oskratch/prestashop-module-pickupscheduler/commit/093a948feabcef09fc768ac93e6dbaa5756af1ed))

## [1.2.0](https://github.com/oskratch/prestashop-module-pickupscheduler/compare/pickupscheduler-v1.1.3...pickupscheduler-v1.2.0) (2026-07-01)


### Features

* add pickup info to invoice and update documentation ([f8fabfa](https://github.com/oskratch/prestashop-module-pickupscheduler/commit/f8fabfacf51258827e417d23942236b840f0e835))
* start version management with release-please ([33513ae](https://github.com/oskratch/prestashop-module-pickupscheduler/commit/33513ae8df499713e867260ab5cfdbbe38d0d171))


### Bug Fixes

* enforce minimum 4-minute interval to prevent infinite loops ([e6b1bf8](https://github.com/oskratch/prestashop-module-pickupscheduler/commit/e6b1bf879cb272a14afec93bf684b01df10fcf7b))
* harden pickup scheduling against IDOR, stale slots and PHP 8.3 issues ([de50147](https://github.com/oskratch/prestashop-module-pickupscheduler/commit/de5014777ed7ef53b7cec6faeab6ad8c98de21ca))
* limit available days to 10 ([6623d68](https://github.com/oskratch/prestashop-module-pickupscheduler/commit/6623d684483eb06488daf7b0306ecf04ec911902))
* restore required release-type input for release-please-action ([1c49a63](https://github.com/oskratch/prestashop-module-pickupscheduler/commit/1c49a63b06d5d87d63041bc11e25dbd08b5009ac))
* skip to next day when current day has no available slots ([6992477](https://github.com/oskratch/prestashop-module-pickupscheduler/commit/6992477e6ac8dd2eeb7db7697f1aeb93fccff235))
* switch to maintained release-please-action v4 and resync version 1.1.3 ([2616635](https://github.com/oskratch/prestashop-module-pickupscheduler/commit/2616635b9b2929176287c530e8bfb9288400b93b))


### Miscellaneous Chores

* **main:** release 1.0.0 ([7cc9359](https://github.com/oskratch/prestashop-module-pickupscheduler/commit/7cc9359311a4d0141b053bdd015a054198ed8792))
* **main:** release 1.0.0 ([9694718](https://github.com/oskratch/prestashop-module-pickupscheduler/commit/96947188475d5bcc80cf24aad049727d6309951f))
* **main:** release 1.1.0 ([#2](https://github.com/oskratch/prestashop-module-pickupscheduler/issues/2)) ([26245c3](https://github.com/oskratch/prestashop-module-pickupscheduler/commit/26245c32396cd22f4ea811743b3133c20b8de4b3))
* **main:** release 1.1.1 ([#3](https://github.com/oskratch/prestashop-module-pickupscheduler/issues/3)) ([e2baad3](https://github.com/oskratch/prestashop-module-pickupscheduler/commit/e2baad3b99df8e1db0ed30db7f8768fd34b4893c))
* **main:** release 1.1.2 ([#4](https://github.com/oskratch/prestashop-module-pickupscheduler/issues/4)) ([8e64db4](https://github.com/oskratch/prestashop-module-pickupscheduler/commit/8e64db40a51e1d1c442bf7e956e57c26a8ef2496))
* **main:** release 1.1.3 ([#5](https://github.com/oskratch/prestashop-module-pickupscheduler/issues/5)) ([6a54fee](https://github.com/oskratch/prestashop-module-pickupscheduler/commit/6a54fee65d801b48e48c9449e74b223482c1fd48))
* sync module version files via release-please and ignore CLAUDE.md ([22103e0](https://github.com/oskratch/prestashop-module-pickupscheduler/commit/22103e0cec657dbda5799be7b49dfae2deaa2700))

## [1.1.3](https://github.com/oskratch/prestashop-module-pickupscheduler/compare/v1.1.2...v1.1.3) (2026-07-01)


### Bug Fixes

* harden pickup scheduling against IDOR, stale slots and PHP 8.3 issues ([de50147](https://github.com/oskratch/prestashop-module-pickupscheduler/commit/de5014777ed7ef53b7cec6faeab6ad8c98de21ca))
* restore required release-type input for release-please-action ([1c49a63](https://github.com/oskratch/prestashop-module-pickupscheduler/commit/1c49a63b06d5d87d63041bc11e25dbd08b5009ac))


### Miscellaneous Chores

* sync module version files via release-please and ignore CLAUDE.md ([22103e0](https://github.com/oskratch/prestashop-module-pickupscheduler/commit/22103e0cec657dbda5799be7b49dfae2deaa2700))

## [1.1.2](https://github.com/oskratch/prestashop-module-pickupscheduler/compare/v1.1.1...v1.1.2) (2025-12-29)


### Bug Fixes

* enforce minimum 4-minute interval to prevent infinite loops ([e6b1bf8](https://github.com/oskratch/prestashop-module-pickupscheduler/commit/e6b1bf879cb272a14afec93bf684b01df10fcf7b))

## [1.1.1](https://github.com/oskratch/prestashop-module-pickupscheduler/compare/v1.1.0...v1.1.1) (2025-12-29)


### Bug Fixes

* limit available days to 10 ([6623d68](https://github.com/oskratch/prestashop-module-pickupscheduler/commit/6623d684483eb06488daf7b0306ecf04ec911902))
* skip to next day when current day has no available slots ([6992477](https://github.com/oskratch/prestashop-module-pickupscheduler/commit/6992477e6ac8dd2eeb7db7697f1aeb93fccff235))

## [1.1.0](https://github.com/oskratch/prestashop-module-pickupscheduler/compare/v1.0.0...v1.1.0) (2025-07-09)


### Features

* add pickup info to invoice and update documentation ([f8fabfa](https://github.com/oskratch/prestashop-module-pickupscheduler/commit/f8fabfacf51258827e417d23942236b840f0e835))

## 1.0.0 (2025-05-30)


### Features

* start version management with release-please ([33513ae](https://github.com/oskratch/prestashop-module-pickupscheduler/commit/33513ae8df499713e867260ab5cfdbbe38d0d171))
