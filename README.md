# Pickup Scheduler
**Version:** 1.0.0  
**Compatible with PrestaShop:** 1.7.x and 1.8.x  

## Description
This module allows customers to select an available time slot and day to pick up their order from the store. It can be associated with an already created carrier (e.g., Click and Collect) and configured to meet the store's specific needs.

## Main Features
- **Carrier Association:** Associate the module with an existing carrier for pickup (e.g., Click and Collect).
- **Order Preparation Time:** Define the number of days the store needs to prepare an order. Customers can only select pickup dates starting from this period, ensuring enough time for order preparation.
- **Reservation Time:** Set the number of minutes the selected time slot is reserved before completing the order. If the customer exceeds this limit, the time slot will be released and available for other customers.
- **Time Slot Configuration:** For each day, enable or disable the pickup option, set the start and end times, and configure the interval in minutes between each available time slot.
- **Available Days Configuration:** Set the number of days for which time slots will always be available for pickup. This value ensures that time slots are generated automatically to maintain the indicated availability.

## Installation
1. Compress the module folder (`pickupscheduler/`) into a `.zip` file.
2. Access the PrestaShop admin panel.
3. Go to **Modules and Services** > **Upload a module**.
4. Upload the `.zip` file and activate the module.

## Configuration
1. Once installed, access the module configuration from the admin panel.
2. Customize the available options according to your store's needs by accessing the module configuration from the Configure option in the module list.
3. Under the Shipping menu in the backoffice, a new subsection called Pickup Scheduler will appear, where you can configure the time slots and other settings.

## Show Pickup Information on PDF Invoice

To display the pickup date and time on the PDF invoice, you need to override the `invoice.tpl` template in your theme (or child theme):

1. Copy the original `invoice.tpl` from your main theme (usually located at `themes/your-theme/pdf/invoice.tpl`) to your child theme or custom theme if it does not already exist.
2. Open the copied `invoice.tpl` file.
3. After the shipping information block, add the following line:

   ```smarty
   {hook h='displayInvoice' id_order=$order->id}
   ```

   This will display the pickup date and time for orders with a scheduled pickup, using the Pickup Scheduler module.

## Notes
This module is ideal for stores that offer in-store pickup and need to manage time slots and order preparation times effectively.

## Support
If you need help or have questions about this module, you can contact `oskratch@gmail.com`.

## License
This plugin is licensed under the GPLv2 or later. See [LICENSE](LICENSE) for details.
