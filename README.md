# Pickup Scheduler

[![PrestaShop](https://img.shields.io/badge/PrestaShop-1.7.x%20%7C%208.x-blue)](https://www.prestashop.com/)
[![PHP](https://img.shields.io/badge/PHP-7.2%2B-blue)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-GPL--2.0-green.svg)](LICENSE)

## Overview
A professional PrestaShop module that enables in-store pickup scheduling for customers. The module seamlessly integrates with existing carriers and provides flexible time slot management to optimize store operations and customer experience.

**Perfect for businesses offering "Click & Collect" services.**

## ✨ Key Features

- 🚚 **Carrier Integration**: Seamlessly associate with existing carriers (Click & Collect, Store Pickup, etc.)
- ⏰ **Smart Order Preparation**: Configure preparation time to ensure orders are ready for pickup
- 🔒 **Time Slot Reservations**: Automatic reservation system with configurable timeout
- 📅 **Flexible Scheduling**: Customize daily time slots with specific intervals and availability
- 🔄 **Automated Management**: Auto-generate time slots to maintain consistent availability
- 📄 **Invoice Integration**: Display pickup information directly on PDF invoices

## 🚀 Installation

### Step 1: Download & Upload
1. Download or clone this repository
2. Compress the `pickupscheduler/` folder into a `.zip` file
3. Access your PrestaShop admin panel
4. Navigate to **Modules and Services** → **Upload a module**
5. Upload the `.zip` file and click **Install**

### Step 2: Activation
After installation, the module will be automatically activated and ready for configuration.

## ⚙️ Configuration

### Initial Setup
1. Navigate to **Modules and Services** → **Pickup Scheduler** → **Configure**
2. Configure your store's specific requirements and preferences
3. Review confirmed pickups anytime under **Orders** → **Recogidas en tienda** in the backoffice

### Key Configuration Options
- **Carrier Association**: Link with your existing pickup/collect carrier
- **Preparation Time**: Set minimum days needed for order preparation
- **Reservation Timeout**: Configure how long time slots are held during checkout
- **Daily Availability & Time Slot Intervals**: Enable/disable pickup per weekday and define its opening hours and slot duration (minimum 4 minutes)
- **Available Days Window**: Set how many days ahead (up to 10) always have time slots ready to book

## 📄 PDF Invoice Integration

To display pickup information on PDF invoices, follow these steps:

### Implementation Steps
1. **Locate Template**: Find your theme's `invoice.tpl` file (usually at `themes/your-theme/pdf/invoice.tpl`)
2. **Create Override**: Copy the template to your child theme if not already present
3. **Add Hook**: Insert the following line after the shipping information block:

```smarty
{hook h='displayInvoice' id_order=$order->id}
```

This integration automatically displays pickup date and time for scheduled orders on all generated invoices.

## 💼 Use Cases

**Perfect for businesses that offer:**
- Click & Collect services
- In-store pickup options
- Appointment-based collection
- Scheduled order fulfillment
- Time-sensitive product pickups

## 📞 Support & Contact

For technical support, feature requests, or questions:
- **Email**: [oskratch@gmail.com](mailto:oskratch@gmail.com)
- **Issues**: Use the [GitHub Issues](../../issues) page for bug reports

## 📄 License

This module is licensed under **GPL-2.0**. See the [LICENSE](LICENSE) file for full details.
