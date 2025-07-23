<?php
// This is a minimalist header for report pages
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? $title : 'Report'; ?> - THE KRISLIZZ INTERNATIONAL ACADEMY INC.</title>
    
    <!-- Google Fonts - Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <?php if (isset($extra_css)) echo $extra_css; ?>
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            padding: 20px;
        }
        
        .report-header {
            text-align: center;
            margin-bottom: 30px;
            position: relative;
            border-bottom: 3px double #333;
            padding-bottom: 15px;
        }
        
        .school-info {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }
        
        .school-logo {
            width: 100px;
            height: auto;
            margin-right: 20px;
        }
        
        .school-details {
            text-align: center;
        }
        
        .school-name {
            font-size: 24pt;
            font-weight: bold;
            margin-bottom: 5px;
            color: #003366;
            text-transform: uppercase;
        }
        
        .school-address {
            font-size: 10pt;
            margin-bottom: 2px;
            color: #555;
        }
        
        .school-slogan {
            font-size: 11pt;
            font-style: italic;
            margin-top: 5px;
            color: #666;
        }
        
        .report-title {
            margin: 15px 0 5px;
            font-size: 18pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #003366;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
        }
        
        .report-date {
            font-size: 10pt;
            color: #666;
            margin-bottom: 0;
        }
        
        .decorative-border {
            height: 5px;
            background: linear-gradient(to right, #003366, #4e73df, #003366);
            margin: 10px 0 20px;
            border-radius: 2px;
        }
        
        .table-info {
            margin-bottom: 15px;
            font-size: 10pt;
            background-color: #f8f9fa;
            padding: 10px;
            border-left: 4px solid #4e73df;
        }
        
        @media print {
            body {
                padding: 0;
            }
            
            .report-header {
                position: running(header);
                border-bottom: 2px solid #000;
            }
            
            @page {
                @top-center {
                    content: element(header);
                }
                margin-top: 2.5cm;
            }
            
            .decorative-border {
                background: #003366;
                height: 3px;
            }
            
            thead {
                display: table-header-group;
            }
            
            tfoot {
                display: table-footer-group;
            }
            
            table {
                page-break-inside: auto;
            }
            
            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="report-header">
            <div class="school-info">
                <img src="<?php echo $relative_path; ?>assets/images/logo.jpg" alt="School Logo" class="school-logo">
                <div class="school-details">
                    <div class="school-name">THE KRISLIZZ INTERNATIONAL ACADEMY INC.</div>
                    <div class="school-address">School Address Line 1</div>
                    <div class="school-address">School Address Line 2</div>
                    <div class="school-slogan">Quality Education is our COMMITMENT</div>
                </div>
            </div>
            <div class="decorative-border"></div>
            <h1 class="report-title"><?php echo isset($title) ? $title : 'Report'; ?></h1>
            <p class="report-date">Generated on: <?php echo date('F d, Y h:i A'); ?></p>
        </div> 