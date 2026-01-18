<?php
/**
 * Analytics Page Template
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!Stand120_Auth::is_admin()) {
    wp_redirect(home_url('/120-stand/'));
    exit;
}

$page_title = 'Analytics - 120 Stand Inventory';
$current_user = Stand120_Auth::get_current_user_data();

include STAND120_PLUGIN_DIR . 'templates/partials/header.php';
?>

<h1 class="page-title">
    <i class="fas fa-chart-line"></i>
    Analytics Dashboard
</h1>

<!-- Date Range Filter -->
<div class="filter-section">
    <div class="filter-group">
        <label>From Date</label>
        <input type="date" id="dateFrom" class="form-control" value="<?php echo date('Y-m-01'); ?>">
    </div>
    <div class="filter-group">
        <label>To Date</label>
        <input type="date" id="dateTo" class="form-control" value="<?php echo date('Y-m-d'); ?>">
    </div>
    <div class="filter-group">
        <label>&nbsp;</label>
        <button id="loadAnalytics" class="btn btn-primary">
            <i class="fas fa-sync"></i> Load Analytics
        </button>
    </div>
</div>

<!-- Overview Cards -->
<div class="summary-cards">
    <div class="summary-card glass-card">
        <div class="summary-card-icon">
            <i class="fas fa-naira-sign"></i>
        </div>
        <span class="summary-card-label">Total Sales</span>
        <span id="totalSalesAnalytics" class="summary-card-value">₦0</span>
    </div>
    
    <div class="summary-card glass-card">
        <div class="summary-card-icon">
            <i class="fas fa-shopping-cart"></i>
        </div>
        <span class="summary-card-label">Total Orders</span>
        <span id="totalOrdersAnalytics" class="summary-card-value">0</span>
    </div>
    
    <div class="summary-card glass-card">
        <div class="summary-card-icon">
            <i class="fas fa-calculator"></i>
        </div>
        <span class="summary-card-label">Avg Order Value</span>
        <span id="avgOrderAnalytics" class="summary-card-value">₦0</span>
    </div>
</div>

<!-- Staff Performance -->
<div class="glass-card">
    <h3 style="margin-bottom: 20px; color: var(--primary-color);">
        <i class="fas fa-users"></i> Staff Performance
    </h3>
    
    <div class="table-responsive">
        <table class="table" id="staffPerformanceTable">
            <thead>
                <tr>
                    <th>Staff Name</th>
                    <th>Orders</th>
                    <th>Total Sales</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<!-- Top Products -->
<div class="glass-card">
    <h3 style="margin-bottom: 20px; color: var(--primary-color);">
        <i class="fas fa-trophy"></i> Top Selling Products
    </h3>
    
    <div class="table-responsive">
        <table class="table" id="topProductsTable">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Quantity Sold</th>
                    <th>Revenue</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<!-- Daily Sales Chart (Simple visualization) -->
<div class="glass-card">
    <h3 style="margin-bottom: 20px; color: var(--primary-color);">
        <i class="fas fa-chart-bar"></i> Daily Sales Trend
    </h3>
    
    <div id="dailySalesChart" style="display: flex; flex-wrap: wrap; gap: 8px; align-items: flex-end; min-height: 200px;">
        <!-- Simple bar chart rendered via JS -->
    </div>
</div>

<script>
    $(document).ready(function() {
        loadAnalytics();
        
        $('#loadAnalytics').on('click', loadAnalytics);
    });
    
    function loadAnalytics() {
        Stand120.ajax('get_analytics', {
            type: 'overview',
            date_from: $('#dateFrom').val(),
            date_to: $('#dateTo').val()
        }).then(response => {
            if (response.success) {
                const data = response.data.analytics;
                
                // Update overview cards
                const totalSales = parseFloat(data.total_sales) || 0;
                const totalOrders = parseInt(data.total_orders) || 0;
                const avgOrder = totalOrders > 0 ? totalSales / totalOrders : 0;
                
                $('#totalSalesAnalytics').text('₦' + Stand120.formatNumber(totalSales));
                $('#totalOrdersAnalytics').text(Stand120.formatNumber(totalOrders));
                $('#avgOrderAnalytics').text('₦' + Stand120.formatNumber(avgOrder));
                
                // Render staff performance
                const $staffBody = $('#staffPerformanceTable tbody').empty();
                if (data.staff_performance && data.staff_performance.length > 0) {
                    data.staff_performance.forEach(staff => {
                        $staffBody.append(`<tr>
                            <td>${staff.full_name}</td>
                            <td>${Stand120.formatNumber(staff.order_count || 0)}</td>
                            <td class="formatted-number">₦${Stand120.formatNumber(staff.total_sales || 0)}</td>
                        </tr>`);
                    });
                } else {
                    $staffBody.append('<tr><td colspan="3" style="text-align:center;color:var(--text-muted)">No data</td></tr>');
                }
                
                // Render top products
                const $productsBody = $('#topProductsTable tbody').empty();
                if (data.top_products && data.top_products.length > 0) {
                    data.top_products.forEach(product => {
                        $productsBody.append(`<tr>
                            <td>${product.product_name}</td>
                            <td>${Stand120.formatNumber(product.qty_sold)}</td>
                            <td class="formatted-number">₦${Stand120.formatNumber(product.revenue)}</td>
                        </tr>`);
                    });
                } else {
                    $productsBody.append('<tr><td colspan="3" style="text-align:center;color:var(--text-muted)">No data</td></tr>');
                }
                
                // Render simple bar chart for daily sales
                const $chartContainer = $('#dailySalesChart').empty();
                if (data.daily_sales && data.daily_sales.length > 0) {
                    const maxSales = Math.max(...data.daily_sales.map(d => parseFloat(d.total) || 0));
                    
                    data.daily_sales.forEach(day => {
                        const height = maxSales > 0 ? ((parseFloat(day.total) || 0) / maxSales * 150) : 0;
                        const date = new Date(day.order_date);
                        const label = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                        
                        $chartContainer.append(`
                            <div style="display: flex; flex-direction: column; align-items: center; flex: 1; min-width: 40px; max-width: 60px;">
                                <div style="width: 100%; height: ${Math.max(height, 5)}px; background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%); border-radius: 4px 4px 0 0;" title="₦${Stand120.formatNumber(day.total)}"></div>
                                <span style="font-size: 0.7rem; color: var(--text-muted); margin-top: 4px; text-align: center;">${label}</span>
                            </div>
                        `);
                    });
                } else {
                    $chartContainer.html('<p style="color: var(--text-muted); text-align: center; width: 100%;">No sales data for this period</p>');
                }
            }
        });
    }
</script>

<?php include STAND120_PLUGIN_DIR . 'templates/partials/footer.php'; ?>
