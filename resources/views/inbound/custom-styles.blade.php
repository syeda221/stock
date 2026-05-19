<style>
/* Inbound Table Enhanced Styling */
.hover-shadow {
    transition: all 0.3s ease;
}

.hover-shadow:hover {
    background-color: #f8f9fa !important;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    transform: translateY(-2px);
}

.table-primary th {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    font-weight: 600;
    border: none;
    padding: 12px 8px;
    font-size: 13px;
}

.table-hover tbody tr:hover {
    background-color: #f0f4ff;
}

.badge {
    font-weight: 500;
    letter-spacing: 0.3px;
}

.btn-group .btn {
    border-radius: 6px !important;
    margin: 0 2px;
}

.btn-outline-danger:hover {
    background-color: #dc3545;
    color: white;
    transform: scale(1.05);
}

.btn-outline-primary:hover {
    background-color: #0d6efd;
    color: white;
    transform: scale(1.05);
}

.form-select-sm {
    font-size: 12px;
    padding: 4px 8px;
    border-radius: 6px;
}

.qc-status-select {
    cursor: pointer;
    transition: all 0.2s;
}

.qc-status-select:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

/* Product icon circle */
.bg-primary.bg-opacity-10 {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Responsive improvements */
@media (max-width: 768px) {
    .table-responsive {
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
}
</style>
