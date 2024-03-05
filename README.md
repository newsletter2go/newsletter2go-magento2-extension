# Newsletter2Go Magento 2 Extension

The Newsletter2Go Magento 2 Extension allows you to seamlessly integrate your Magento 2.4 store with the Newsletter2Go email marketing platform. With this extension, you can easily synchronize your customer data, create targeted email campaigns, and track the performance of your newsletters.

## Features

- **Customer Data Synchronization**: Automatically sync your customer data between Magento 2.4 and Newsletter2Go, ensuring that your email lists are always up to date.
- **Segmentation and Personalization**: Create targeted email campaigns by segmenting your customers based on various criteria, such as purchase history, demographics, or behavior.
- **Automated Campaigns**: Set up automated email campaigns, such as welcome emails, abandoned cart reminders, or birthday greetings, to engage with your customers at the right time.
- **Performance Tracking**: Monitor the performance of your newsletters with detailed analytics and reports, including open rates, click-through rates, and conversions.

## Installation

To install the Newsletter2Go Magento 2 Extension, follow these steps:

1. Download the extension package from the releases here on GitHub.
2. Extract the contents of the package to the `app/code/Newsletter2Go` directory of your Magento 2.4 installation.
3. Run the following commands from the root directory of your Magento 2.4 installation:

    ```bash
    bin/magento module:enable Newsletter2Go_Extension
    bin/magento setup:upgrade
    bin/magento setup:di:compile
    bin/magento cache:clean
    ```

4. Log in to your Magento 2.4 admin panel and navigate to **Stores > Configuration > Newsletter2Go** to configure the extension settings.
5. Enter your Newsletter2Go API credentials and adjust any other settings according to your preferences.
6. Save the configuration and you're ready to start using the Newsletter2Go extension!

## Support

If you encounter any issues or have any questions, please contact our support team at support@newsletter2go.com

## License

This extension is released under the [GPL-3.0 license](LICENSE).
