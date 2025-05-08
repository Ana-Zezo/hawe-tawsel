<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Error</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f8f9fa;
            margin: 0;
            font-family: Arial, sans-serif;
        }

        .animation-ctn {
            text-align: center;
        }

        @keyframes draw-circle {
            0% {
                stroke-dashoffset: 480;
            }

            100% {
                stroke-dashoffset: 0;
            }
        }

        @keyframes fade-in {
            0% {
                opacity: 0;
            }

            100% {
                opacity: 1;
            }
        }

        /* تنسيق SVG */
        .icon--order-error svg {
            width: 154px;
            height: 154px;
        }

        .icon--order-error svg circle {
            fill: none;
            stroke: #dc3545;
            stroke-width: 2;
            stroke-dasharray: 480;
            stroke-dashoffset: 480;
            animation: draw-circle 0.6s ease-in-out forwards;
        }

        .icon--order-error svg circle#colored {
            fill: #dc3545;
            stroke: none;
            opacity: 0;
            animation: fade-in 0.6s ease-in-out 0.7s forwards;
        }

        /* علامة X بدون أنيميشن */
        .icon--order-error svg polyline {
            stroke: #fff;
            stroke-width: 10;
            stroke-linecap: round;
            opacity: 0;
            animation: fade-in 0.3s ease-in-out 0.9s forwards;
        }
    </style>
</head>

<body>
    <div class="animation-ctn">
        <div class="icon icon--order-error svg">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 154 154">
                <circle cx="77" cy="77" r="72" />
                <circle id="colored" cx="77" cy="77" r="72" />
                <polyline points="30,30 124,124" />
                <polyline points="124,30 30,124" />
            </svg>
        </div>
        <br />
        <h2>Payment Error</h2>
        {{-- <p>Ref Id: {{ $transaction->paymentId }}</p> --}}
    </div>
</body>

</html>
