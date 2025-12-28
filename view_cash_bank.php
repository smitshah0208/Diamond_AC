<?php include "config/db.php"; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>View Cash/Bank Book</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/cash_bank.css">
    <style>
        .filter-box {
            background: #f8fafc;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            margin-bottom: 25px;
        }

        .filter-row {
            display: flex;
            gap: 20px;
            align-items: flex-end;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }

        .filter-field {
            flex: 1;
            min-width: 200px;
        }

        .filter-field label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 5px;
        }

        .search-btn {
            background: #0f172a;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
        }

        .search-btn:hover {
            background: #334155;
        }

        .totals-row {
            background: #f1f5f9;
            font-weight: bold;
            font-size: 15px;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-cash {
            background: #dcfce7;
            color: #166534;
        }

        .badge-bank {
            background: #dbeafe;
            color: #1e40af;
        }
    </style>
</head>

<body>

    <div class="container" style="max-width: 1200px;">
        <div class="header">
            <h2>📂 View Cash & Bank Reports</h2>
            <a href="cash_bank_entry.php" style="text-decoration:none;">
                <button class="btn-add">Go to Entry Page</button>
            </a>
        </div>

        <!-- FILTERS -->
        <div class="filter-box">
            <form id="filterForm">
                <!-- Row 1: Date Range -->
                <div class="filter-row">
                    <div class="filter-field">
                        <label>From Date</label>
                        <input type="date" id="fromDate">
                    </div>
                    <div class="filter-field">
                        <label>To Date</label>
                        <input type="date" id="toDate">
                    </div>
                    <div class="filter-field">
                        <label>Search Criteria</label>
                        <select id="searchType" onchange="toggleSearchInput()">
                            <option value="ALL">Show All (Date Wise)</option>
                            <option value="INVOICE">By Invoice Number</option>
                            <option value="PARTY">By Party Name</option>
                            <option value="BROKER">By Broker Name</option>
                        </select>
                    </div>
                </div>

                <!-- Row 2: Dynamic Search Input -->
                <div class="filter-row">
                    <div class="filter-field autocomplete" id="dynamicSearchBox" style="display:none; flex:2;">
                        <label id="dynamicLabel">Search Term</label>
                        <input id="searchInput" placeholder="Type to search..." autocomplete="off">
                        <div id="searchSug" class="autocomplete-list"></div>
                    </div>

                    <div style="flex:1;">
                        <label>&nbsp;</label>
                        <button type="submit" class="search-btn" style="width:100%">🔍 Search Records</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- ... existing head ... -->
        <!-- RESULTS TABLE -->
        <table id="resultsTable">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Particulars / Party</th>
                    <th>Ref Invoice</th>
                    <th style="text-align:right">Debit (Local)</th>
                    <th style="text-align:right">Credit (Local)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="6" class="empty-row">Select filters and click Search.</td>
                </tr>
            </tbody>
            <tfoot>
                <tr class="totals-row">
                    <td colspan="4" style="text-align:right;">TOTALS (Local):</td>
                    <td style="text-align:right;" id="totalDr">0.00</td>
                    <td style="text-align:right;" id="totalCr">0.00</td>
                </tr>
            </tfoot>
        </table>
        <!-- ... -->
        <script src="assets/js/view_cash_bank.js"></script>
</body>

</html>