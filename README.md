# Pickup Scheduler

[![PrestaShop](https://img.shields.io/badge/PrestaShop-1.7.x%20%7C%208.x%20%7C%209.x-blue)](https://www.prestashop.com/)
[![PHP](https://img.shields.io/badge/PHP-7.2%2B-blue)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-GPL--2.0-green.svg)](LICENSE)

PrestaShop module for Click & Collect: at checkout, customers pick the day and time they will collect their order in store. It works on top of the pickup carrier you already have.

## Features

- **Carrier link**: attach the module to an existing pickup carrier (e.g. "Store pickup").
- **Preparation time**: the minimum number of days the store needs before an order can be collected.
- **Slot reservation**: the chosen slot is held while the customer finishes checkout and released if the time runs out.
- **Opening hours per weekday**: enable or disable pickup for each day, with its own hours and slot length.
- **Rolling availability**: slots are generated automatically so the next days (up to 10) are always bookable.
- **PDF invoices**: the pickup date and time can be printed on the order invoice.

## Installation

1. Download or clone this repository.
2. Compress the `pickupscheduler/` folder into a `.zip` file.
3. In the PrestaShop back office, go to **Modules and Services** → **Upload a module**.
4. Upload the `.zip` file and click **Install**.

The module is enabled on install and ready to configure.

## Configuration

Go to **Modules and Services** → **Pickup Scheduler** → **Configure**. Confirmed pickups can be reviewed at any time under **Orders** → **Recogidas en tienda**.

| Option | What it does |
|--------|--------------|
| Carrier | The existing pickup carrier the module works with |
| Preparation time | Minimum days before an order can be collected |
| Reservation timeout | How long a slot is held during checkout |
| Daily availability and slot interval | Pickup on/off per weekday, opening hours and slot length (minimum 4 minutes) |
| Available days window | How many days ahead (up to 10) always have slots ready to book |

## PDF invoice

To show the pickup date and time on PDF invoices:

1. Find your theme's `invoice.tpl` (usually `themes/your-theme/pdf/invoice.tpl`).
2. Copy it to your child theme if it isn't there already.
3. Add this line after the shipping information block:

```smarty
{hook h='displayInvoice' id_order=$order->id}
```

Invoices for orders with a scheduled pickup will then include its date and time.

## Support

- Email: [oskratch@gmail.com](mailto:oskratch@gmail.com)
- Bug reports: [GitHub Issues](../../issues)

## License

GPL-2.0. See [LICENSE](LICENSE).
