<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Real-Time Sales Analytics</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .card { border: 1px solid #ddd; padding: 20px; margin: 10px 0; border-radius: 10px; background: #f9f9f9; }
        #dashboard .card { display: inline-block; width: 23%; vertical-align: top; margin-right: 1%; }
        .card h3 { margin-top: 0; }
        form input, form select, form button { display: block; width: 100%; margin: 10px 0; padding: 10px; }
        #recommendationsBox, #weatherBox { margin-top: 20px; }
    </style>


</head>
<body>
<h1>Real-Time Sales Analytics Dashboard</h1>

<!-- Add Order Form -->
<div class="card">
    <h3>Add New Order</h3>
    <div id="successMsg" style="text-align: center;color: #0a0ac8" ></div>

    <form id="orderForm">
        <select name="product_id" required>
            <option value="1">Product 1</option>
            <option value="2">Product 2</option>
            <option value="3">Product 3</option>
        </select>
        <input type="number" name="quantity" placeholder="Quantity" required>
        <input type="number" name="price" placeholder="Price" required>
        <button type="submit">Add Order</button>
    </form>
</div>

<!-- Analytics Dashboard -->
<div id="dashboard">
    <div class="card">
        <h3>Total Revenue</h3>
        <p id="totalRevenue">0</p>
    </div>
    <div class="card">
        <h3>Top Products</h3>
        <p id="topProducts">-</p>
    </div>
    <div class="card">
        <h3>Last Minute Revenue</h3>
        <p id="lastMinuteRevenue">0</p>
    </div>
    <div class="card">
        <h3>Orders in Last Minute</h3>
        <p id="lastMinuteOrders">0</p>
    </div>
</div>

<!-- AI Recommendations -->
<div class="card">
    <h3>AI Product Recommendations</h3>
    <button onclick="getRecommendations()">Get Recommendations</button>
    <div id="recommendationsBox">-</div>
</div>

<!-- Weather Suggestions -->
<div class="card">
    <h3>Weather-Based Suggestion</h3>
    <button onclick="getWeather()">Check Weather</button>
    <div id="weatherBox">-</div>
</div>


<script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>
<script>
    // Enable Pusher logging - don't include this in production
    Pusher.logToConsole = true;

    var pusher = new Pusher('636df6607f28e5bf618f', {
        cluster: 'eu'
    });

    var channel = pusher.subscribe('my-channel');

    channel.bind('my-event', function(response) {
        // Parse the actual data object
        const data = response.data;

        // Update the dashboard elements
        document.getElementById('totalRevenue').innerText = data.total_revenue;
        document.getElementById('topProducts').innerText = data.top_products.join(', ');
        document.getElementById('lastMinuteRevenue').innerText = data.revenue_change_last_minute;
        document.getElementById('lastMinuteOrders').innerText = data.orders_count_last_minute;
    });
</script>

<script>
    document.getElementById('orderForm').addEventListener('submit', async function (e) {
        e.preventDefault();
        const formData = new FormData(this);
        const body = Object.fromEntries(formData.entries());
        const res = await fetch('/api/orders', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });
        const data = await res.json();
        document.getElementById('successMsg').innerText = 'Order added successfully!';
    });


    async function fetchAnalytics() {
        try {
            const res = await fetch('/api/analytics');
            if (!res.ok) throw new Error('Network response was not ok');
            const data = await res.json();
            document.getElementById('totalRevenue').innerText = data.total_revenue;
            document.getElementById('topProducts').innerText = data.top_products;
            document.getElementById('lastMinuteRevenue').innerText = data.revenue_change_last_minute;
            document.getElementById('lastMinuteOrders').innerText = data.orders_count_last_minute;
        } catch (error) {
            console.error('Failed to fetch analytics:', error);
        }
    }
    // استدعاء الدالة أول مرة لتعبئة البيانات عند تحميل الصفحة
    fetchAnalytics();


    async function getRecommendations() {
        const res = await fetch('/api/ai-recommendation');
        const data = await res.json();
        document.getElementById('recommendationsBox').innerText = data.recommendations || 'No suggestions available.';
    }

    async function getWeather() {
        const res = await fetch('/weather-suggestions');
        const data = await res.json();
        document.getElementById('weatherBox').innerText = data.suggestion || 'No suggestion available.';
    }
</script>
</body>
</html>
