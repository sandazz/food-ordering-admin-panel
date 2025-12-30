<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - My Food App</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
            padding: 20px;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background-color: #fff;
            padding: 40px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
        
        h1 {
            color: #2c3e50;
            font-size: 2.2em;
            margin-bottom: 10px;
            border-bottom: 3px solid #3498db;
            padding-bottom: 15px;
        }
        
        h2 {
            color: #2c3e50;
            font-size: 1.6em;
            margin-top: 30px;
            margin-bottom: 15px;
            padding-top: 10px;
        }
        
        h3 {
            color: #34495e;
            font-size: 1.2em;
            margin-top: 20px;
            margin-bottom: 10px;
        }
        
        p {
            margin-bottom: 15px;
            text-align: justify;
        }
        
        ul {
            margin-left: 30px;
            margin-bottom: 15px;
        }
        
        li {
            margin-bottom: 8px;
        }
        
        .effective-date {
            background-color: #ecf0f1;
            padding: 15px;
            border-left: 4px solid #3498db;
            margin-bottom: 30px;
            font-weight: 600;
        }
        
        .contact-box {
            background-color: #e8f5e9;
            padding: 20px;
            border-radius: 5px;
            margin-top: 30px;
            border: 1px solid #4caf50;
        }
        
        .important {
            background-color: #fff3cd;
            padding: 15px;
            border-left: 4px solid #ffc107;
            margin: 20px 0;
        }
        
        a {
            color: #3498db;
            text-decoration: none;
        }
        
        a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }
            
            h1 {
                font-size: 1.8em;
            }
            
            h2 {
                font-size: 1.4em;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Privacy Policy</h1>
        
        <div class="effective-date">
            Effective Date: January 1, 2025
        </div>

        <h2>1. Introduction</h2>
        <p>
            Welcome to My Food App. We are committed to protecting your privacy and ensuring the security of your personal information. This Privacy Policy explains how we collect, use, store, and share your information when you use our mobile application and related services.
        </p>
        <p>
            My Food App is a food ordering platform that allows you to browse restaurant menus, place orders, and make secure payments. We operate in Finland and serve international users. This Privacy Policy complies with the General Data Protection Regulation (GDPR) and applicable data protection laws.
        </p>
        <p>
            By using My Food App, you agree to the collection and use of information in accordance with this policy. If you do not agree with any part of this Privacy Policy, please do not use our services.
        </p>

        <h2>2. Information We Collect</h2>
        <p>
            We collect various types of information to provide and improve our services to you.
        </p>

        <h3>2.1 Personal Information</h3>
        <p>
            When you create an account or use our services, we collect personal information that you provide directly to us, including:
        </p>
        <ul>
            <li><strong>Account Information:</strong> Name, email address, phone number, and password (encrypted)</li>
            <li><strong>Profile Information:</strong> Profile picture, dietary preferences, saved addresses</li>
            <li><strong>Authentication Data:</strong> Information received from Firebase Authentication when you sign in using Email/Password, Google Sign-In, or Apple Sign-In</li>
            <li><strong>Delivery Information:</strong> Delivery addresses, contact details for order fulfillment</li>
        </ul>

        <h3>2.2 Order & Transaction Data</h3>
        <p>
            When you place an order through My Food App, we collect:
        </p>
        <ul>
            <li><strong>Order Details:</strong> Items ordered, quantities, prices, special instructions</li>
            <li><strong>Restaurant Information:</strong> The restaurants you order from and your ordering history</li>
            <li><strong>Transaction Information:</strong> Payment method selection, transaction status, order confirmation details</li>
            <li><strong>Payment Processing:</strong> Payment transactions are processed securely through Paytrail, our third-party payment processor. <strong>We do NOT store or have access to your complete credit or debit card details.</strong> Only transaction confirmation data (such as transaction ID, amount, and status) is stored in our system.</li>
        </ul>

        <h3>2.3 Location Information</h3>
        <p>
            Location data collection is entirely optional and user-controlled:
        </p>
        <ul>
            <li><strong>GPS Location:</strong> With your explicit permission, we may access your device's GPS location to help you select delivery addresses and find nearby restaurants</li>
            <li><strong>Address Information:</strong> Delivery addresses you manually enter or save</li>
            <li><strong>Control:</strong> You can enable or disable location services at any time through your device settings. Location access is not required to use the app; you can manually enter addresses instead</li>
        </ul>

        <h3>2.4 Device & Usage Data</h3>
        <p>
            We automatically collect certain information about your device and how you interact with our app:
        </p>
        <ul>
            <li><strong>Device Information:</strong> Device model, operating system (Android/iOS), app version, unique device identifiers</li>
            <li><strong>Usage Data:</strong> App features used, pages viewed, time spent in the app, search queries</li>
            <li><strong>Log Data:</strong> IP address, access times, error logs, crash reports</li>
            <li><strong>Push Notification Tokens:</strong> Device tokens for sending order status notifications via Firebase Cloud Messaging</li>
        </ul>

        <h2>3. How We Use Your Information</h2>
        <p>
            We use the collected information for the following purposes:
        </p>
        <ul>
            <li><strong>Service Delivery:</strong> To process and fulfill your food orders, facilitate communication between you and restaurants</li>
            <li><strong>Account Management:</strong> To create and manage your account, authenticate your identity, and provide customer support</li>
            <li><strong>Payment Processing:</strong> To process payments securely through our payment service provider</li>
            <li><strong>Communication:</strong> To send you order confirmations, status updates, delivery notifications, and customer support responses</li>
            <li><strong>Personalization:</strong> To remember your preferences, saved addresses, and favorite restaurants to enhance your experience</li>
            <li><strong>Improvement & Analytics:</strong> To analyze app usage patterns, identify bugs, improve app performance, and develop new features</li>
            <li><strong>Security:</strong> To detect and prevent fraud, abuse, and security incidents</li>
            <li><strong>Legal Compliance:</strong> To comply with applicable laws, regulations, and legal processes</li>
        </ul>

        <h2>4. Third-Party Services</h2>
        <p>
            We use trusted third-party services to operate our app. These services may collect and process your information on our behalf.
        </p>

        <h3>4.1 Firebase (Google LLC)</h3>
        <p>
            We use Firebase services provided by Google for:
        </p>
        <ul>
            <li><strong>Firebase Authentication:</strong> User authentication and identity management (Email/Password, Google Sign-In, Apple Sign-In)</li>
            <li><strong>Firebase Firestore:</strong> Database storage for user profiles, orders, cart data, and app content</li>
            <li><strong>Firebase Cloud Messaging:</strong> Push notifications for order status updates</li>
            <li><strong>Firebase Analytics:</strong> App usage analytics and performance monitoring</li>
        </ul>
        <p>
            Firebase operates under Google's Privacy Policy. Learn more at: <a href="https://firebase.google.com/support/privacy" target="_blank">https://firebase.google.com/support/privacy</a>
        </p>

        <h3>4.2 Paytrail (Paytrail Oyj)</h3>
        <p>
            We use Paytrail as our payment service provider to process online card payments securely:
        </p>
        <ul>
            <li>Paytrail handles all sensitive payment card information</li>
            <li><strong>Payment card details (card numbers, CVV codes) are NEVER stored in our app or servers</strong></li>
            <li>Transactions are processed through secure, PCI DSS compliant infrastructure</li>
            <li>We only receive transaction confirmation data (transaction ID, status, amount)</li>
        </ul>
        <p>
            Learn more about Paytrail's privacy practices at: <a href="https://www.paytrail.com/en/privacy-policy" target="_blank">https://www.paytrail.com/en/privacy-policy</a>
        </p>

        <h3>4.3 Other Third-Party Services</h3>
        <p>
            We may use additional third-party services for:
        </p>
        <ul>
            <li>Cloud hosting and infrastructure services</li>
            <li>Customer support and communication tools</li>
            <li>Analytics and app performance monitoring</li>
        </ul>
        <p>
            These providers are contractually obligated to protect your data and use it only for the purposes we specify.
        </p>

        <h2>5. Data Sharing & Disclosure</h2>
        <p>
            We do not sell, rent, or trade your personal information to third parties. We may share your information only in the following circumstances:
        </p>
        <ul>
            <li><strong>Restaurant Partners:</strong> We share necessary order information (items, delivery address, contact details) with restaurants to fulfill your orders</li>
            <li><strong>Service Providers:</strong> We share data with third-party service providers (Firebase, Paytrail) who help us operate our services</li>
            <li><strong>Legal Requirements:</strong> We may disclose information if required by law, court order, or governmental authority</li>
            <li><strong>Business Transfers:</strong> In the event of a merger, acquisition, or sale of assets, your information may be transferred to the new entity</li>
            <li><strong>Protection of Rights:</strong> We may disclose information to protect our rights, property, safety, or the rights of our users and the public</li>
            <li><strong>With Your Consent:</strong> We may share information with your explicit consent for specific purposes</li>
        </ul>

        <h2>6. Data Security</h2>
        <p>
            We take the security of your personal information seriously and implement appropriate technical and organizational measures:
        </p>
        <ul>
            <li><strong>Encryption:</strong> Data transmission is encrypted using SSL/TLS protocols</li>
            <li><strong>Firebase Security:</strong> User data stored in Firebase Firestore is protected by Firebase security rules and authentication</li>
            <li><strong>Password Protection:</strong> User passwords are encrypted and never stored in plain text</li>
            <li><strong>Access Controls:</strong> Access to personal data is restricted to authorized personnel only</li>
            <li><strong>Payment Security:</strong> Payment processing is handled by PCI DSS compliant service providers</li>
            <li><strong>Regular Monitoring:</strong> We regularly monitor for security vulnerabilities and unauthorized access</li>
        </ul>
        <p>
            However, please note that no method of electronic transmission or storage is 100% secure. While we strive to protect your information, we cannot guarantee absolute security.
        </p>

        <h2>7. Data Retention</h2>
        <p>
            We retain your personal information only for as long as necessary to fulfill the purposes outlined in this Privacy Policy:
        </p>
        <ul>
            <li><strong>Account Data:</strong> Retained while your account is active and for a reasonable period after account deletion to comply with legal obligations</li>
            <li><strong>Order History:</strong> Retained for accounting, tax compliance, and dispute resolution purposes (typically 3-7 years as required by law)</li>
            <li><strong>Usage Data:</strong> Aggregated and anonymized usage data may be retained indefinitely for analytics purposes</li>
            <li><strong>Legal Requirements:</strong> Some data may be retained longer if required by law or to defend legal claims</li>
        </ul>
        <p>
            When data is no longer needed, we securely delete or anonymize it.
        </p>

        <h2>8. Your Rights (GDPR Compliance)</h2>
        <p>
            Under the General Data Protection Regulation (GDPR) and applicable data protection laws, you have the following rights regarding your personal information:
        </p>

        <h3>8.1 Right to Access</h3>
        <p>
            You have the right to request access to the personal information we hold about you. You can view and manage most of your information through your account settings in the app.
        </p>

        <h3>8.2 Right to Correction</h3>
        <p>
            You have the right to correct inaccurate or incomplete personal information. You can update your profile information directly in the app, or contact us for assistance.
        </p>

        <h3>8.3 Right to Deletion</h3>
        <p>
            You have the right to request deletion of your personal information. You can delete your account through the app settings, which will remove your personal data subject to legal retention requirements. To request complete data deletion, please contact us using the information provided in Section 12.
        </p>

        <h3>8.4 Right to Data Portability</h3>
        <p>
            You have the right to receive your personal data in a structured, commonly used, and machine-readable format, and to transmit it to another service provider.
        </p>

        <h3>8.5 Right to Withdraw Consent</h3>
        <p>
            Where we rely on your consent to process personal information, you have the right to withdraw that consent at any time. This includes:
        </p>
        <ul>
            <li>Disabling location services through your device settings</li>
            <li>Opting out of push notifications through app settings</li>
            <li>Deleting your account to stop data collection</li>
        </ul>

        <h3>8.6 Right to Object</h3>
        <p>
            You have the right to object to certain types of data processing, including processing for direct marketing purposes.
        </p>

        <h3>8.7 Right to Lodge a Complaint</h3>
        <p>
            If you believe we have not handled your personal information properly, you have the right to lodge a complaint with your local data protection authority.
        </p>

        <div class="important">
            <strong>Exercising Your Rights:</strong> To exercise any of these rights, please contact us using the contact information provided at the end of this policy. We will respond to your request within 30 days.
        </div>

        <h2>9. Children's Privacy</h2>
        <p>
            My Food App is not intended for use by children under the age of 13 (or 16 in certain European jurisdictions). We do not knowingly collect personal information from children under these ages.
        </p>
        <p>
            If you are a parent or guardian and believe that your child has provided us with personal information without your consent, please contact us immediately. We will take steps to remove such information from our systems.
        </p>

        <h2>10. Cookies & Web Technologies</h2>
        <p>
            Our mobile application does not use cookies in the traditional web sense. However, we use similar technologies:
        </p>
        <ul>
            <li><strong>Local Storage:</strong> We store app preferences, session data, and cached content locally on your device to improve app performance</li>
            <li><strong>Analytics SDKs:</strong> Firebase Analytics uses persistent identifiers to track app usage across sessions</li>
            <li><strong>Authentication Tokens:</strong> Secure tokens are stored locally to maintain your logged-in session</li>
        </ul>
        <p>
            If you access our website (if applicable), we may use cookies and similar technologies. You can control cookie settings through your browser preferences.
        </p>

        <h2>11. Changes to This Privacy Policy</h2>
        <p>
            We may update this Privacy Policy from time to time to reflect changes in our practices, technology, legal requirements, or other factors. When we make significant changes, we will:
        </p>
        <ul>
            <li>Update the "Effective Date" at the top of this policy</li>
            <li>Notify you through the app (via push notification or in-app message)</li>
            <li>In some cases, request your consent for material changes</li>
        </ul>
        <p>
            We encourage you to review this Privacy Policy periodically. Your continued use of My Food App after changes are made constitutes acceptance of the updated policy.
        </p>

        <h2>12. Contact Information</h2>
        <div class="contact-box">
            <p>
                If you have any questions, concerns, or requests regarding this Privacy Policy or how we handle your personal information, please contact us:
            </p>
            <p style="margin-top: 15px;">
                <strong>My Food App</strong><br>
                Email: <a href="mailto:privacy@myfoodapp.com">privacy@myfoodapp.com</a><br>
                Support Email: <a href="mailto:support@myfoodapp.com">support@myfoodapp.com</a><br>
            </p>
            <p style="margin-top: 10px;">
                We will respond to your inquiries within 30 days.
            </p>
        </div>

        <div style="margin-top: 40px; padding-top: 20px; border-top: 2px solid #ecf0f1; text-align: center; color: #7f8c8d; font-size: 0.9em;">
            <p>© 2025 My Food App. All rights reserved.</p>
            <p style="margin-top: 10px;">
                This Privacy Policy is effective as of January 1, 2025 and governs the collection, use, and disclosure of personal information through My Food App mobile application and related services.
            </p>
        </div>
    </div>
</body>
</html>
