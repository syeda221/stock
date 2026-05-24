<div id="notificationBar" style="display: none; background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 6px 16px;">
    <div id="notificationContent" class="d-flex flex-wrap align-items-center gap-2" style="font-size: 12px;"></div>
</div>

<script>
(function() {
    var bar = document.getElementById('notificationBar');
    var container = document.getElementById('notificationContent');

    function fetchNotifications() {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', '{{ route("notifications") }}', true);
        xhr.onload = function() {
            if (xhr.status !== 200) return;
            try {
                var data = JSON.parse(xhr.responseText);
            } catch(e) { return; }

            if (!data.has_alerts) {
                bar.style.display = 'none';
                return;
            }

            var html = '';

            if (data.low_stock > 0) {
                html += '<a href="{{ route("opening-stock.index") }}" class="text-decoration-none d-inline-flex align-items-center gap-1 px-2 py-1 rounded-2" style="background:#fef3c7;color:#92400e;">'
                    + '<i class="bi bi-exclamation-triangle-fill" style="font-size:11px;"></i>'
                    + '<span><strong>' + data.low_stock + '</strong> low stock</span>'
                    + '</a>';
            }

            if (data.expiring > 0) {
                html += '<span class="d-inline-flex align-items-center gap-1 px-2 py-1 rounded-2" style="background:#cffafe;color:#155e75;">'
                    + '<i class="bi bi-clock-fill" style="font-size:11px;"></i>'
                    + '<span><strong>' + data.expiring + '</strong> expiring soon</span>'
                    + '</span>';
            }

            if (data.expired > 0) {
                html += '<span class="d-inline-flex align-items-center gap-1 px-2 py-1 rounded-2" style="background:#fee2e2;color:#991b1b;">'
                    + '<i class="bi bi-x-circle-fill" style="font-size:11px;"></i>'
                    + '<span><strong>' + data.expired + '</strong> expired</span>'
                    + '</span>';
            }

            if (data.qc_pending > 0) {
                html += '<span class="d-inline-flex align-items-center gap-1 px-2 py-1 rounded-2" style="background:#e2e8f0;color:#334155;">'
                    + '<i class="bi bi-clipboard-check" style="font-size:11px;"></i>'
                    + '<span><strong>' + data.qc_pending + '</strong> QC pending</span>'
                    + '</span>';
            }

            html += '<button onclick="document.getElementById(\'notificationBar\').style.display=\'none\'" class="btn btn-sm p-0 border-0 ms-auto" style="font-size:14px;color:#94a3b8;" title="Dismiss">&times;</button>';

            container.innerHTML = html;
            bar.style.display = '';
        };
        xhr.send();
    }

    if (bar && container) {
        fetchNotifications();
        setInterval(fetchNotifications, 30000);
    }
})();
</script>
