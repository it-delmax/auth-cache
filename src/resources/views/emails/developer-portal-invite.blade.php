<?php
/**
 * Email template for inviting a user to the Developer Portal.
 *
 * @var string $user_name The name of the user being invited.
 * @var string $user_email The email of the user being invited.
 * @var string $portal_url The URL of the Developer Portal.
 * @var string|null $temp_password Temporary password for the user, if applicable.
 */
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Developer Portal Invite</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
        }
        .content {
            padding: 30px 20px;
        }
        .feature-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 30px 0;
        }
        .feature {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .feature-icon {
            font-size: 32px;
            margin-bottom: 10px;
        }
        .credentials-box {
            background: #e3f2fd;
            border: 2px solid #2196f3;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .btn {
            display: inline-block;
            padding: 15px 30px;
            background: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin: 10px 0;
        }
        .btn:hover {
            background: #218838;
        }
        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #6c757d;
        }
        .steps {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 20px;
            margin: 20px 0;
        }
        .steps ol {
            margin: 0;
            padding-left: 20px;
        }
        @media (max-width: 600px) {
            .feature-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 Developer Portal</h1>
            <p>Dobrodošli u Delmax Developer Experience</p>
        </div>

        <div class="content">
            <p>Pozdrav <strong>{{ $user_name }}</strong>,</p>

            <p>Pozivamo vas da se pridružite našem <strong>Developer Portal-u</strong> - platformi dizajniranoj
            specijalno za developere koji koriste Delmax API-jeve!</p>

            <div class="feature-grid">
                <div class="feature">
                    <div class="feature-icon">🔑</div>
                    <h4>Self-Service Tokeni</h4>
                    <p>Kreirajte i upravljajte svojim API tokenima</p>
                </div>
                <div class="feature">
                    <div class="feature-icon">📚</div>
                    <h4>Live Dokumentacija</h4>
                    <p>Interaktivna dokumentacija sa primerima</p>
                </div>
                <div class="feature">
                    <div class="feature-icon">📊</div>
                    <h4>Analytics & Monitoring</h4>
                    <p>Pratite vaše API pozive u realnom vremenu</p>
                </div>
                <div class="feature">
                    <div class="feature-icon">🧪</div>
                    <h4>API Testing</h4>
                    <p>Testirajte API pozive direktno iz browser-a</p>
                </div>
            </div>

            @if(isset($temp_password))
            <div class="credentials-box">
                <h4>🔐 Vaši pristupni podaci:</h4>
                <p><strong>URL:</strong> <a href="{{ $portal_url }}">{{ $portal_url }}</a></p>
                <p><strong>Email:</strong> {{ $user_email ?? 'Vaš postojeći email' }}</p>
                <p><strong>Privremena lozinka:</strong> <code>{{ $temp_password }}</code></p>
                <p><small>💡 Molimo promenite lozinku nakon prvog prijavljivanja</small></p>
            </div>
            @endif

            <div class="steps">
                <h4>📝 Sledeći koraci:</h4>
                <ol>
                    <li>Kliknite na dugme ispod da otvorite Developer Portal</li>
                    <li>Prijavite se sa vašim podacima</li>
                    <li>Promenite privremenu lozinku (ako je data)</li>
                    <li>Istražite dostupne API-jeve</li>
                    <li>Kreirajte vaš prvi token!</li>
                </ol>
            </div>

            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ $portal_url }}" class="btn">🎯 Otvorite Developer Portal</a>
            </div>

            <h3>🎯 Šta možete raditi u portalu:</h3>
            <ul>
                <li>✅ Kreiranje i upravljanje API tokenima</li>
                <li>✅ Pregled dostupnih API-jeva i dokumentacije</li>
                <li>✅ Testiranje API poziva sa live primerima</li>
                <li>✅ Monitoring API statistika i usage-a</li>
                <li>✅ Zahtevanje pristupa novim API-jima</li>
                <li>✅ Download SDK-jeva i code examples</li>
            </ul>

            <div style="background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0;">
                <strong>💬 Potrebna pomoć?</strong><br>
                Naš developer support tim je tu za vas:<br>
                📧 <a href="mailto:dev-support@delmax.rs">dev-support@delmax.rs</a><br>
                📱 Slack: <a href="#">#developer-support</a>
            </div>
        </div>

        <div class="footer">
            <p>Delmax Developer Portal | <a href="https://docs.dmx.rs">Dokumentacija</a> | <a href="https://status.dmx.rs">API Status</a></p>
            <p>Ovaj poziv je valjan 7 dana. Nakon toga kontaktirajte administratora.</p>
        </div>
    </div>
</body>
</html>
