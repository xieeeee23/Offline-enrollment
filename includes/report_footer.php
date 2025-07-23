        <div class="report-footer mt-5">
            <div class="signature-section">
                <div class="signature-box">
                    <div class="signature-line">Prepared by</div>
                    <div class="signature-title">Registrar</div>
                </div>
                <div class="signature-box">
                    <div class="signature-line">Verified by</div>
                    <div class="signature-title">Admin Officer</div>
                </div>
                <div class="signature-box">
                    <div class="signature-line">Approved by</div>
                    <div class="signature-title">School Principal</div>
                </div>
            </div>
            
            <div class="mt-5 text-center">
                <p class="small text-muted mb-1">© <?php echo date('Y'); ?> THE KRISLIZZ INTERNATIONAL ACADEMY INC. All Rights Reserved.</p>
                <p class="small text-muted">This is an official document of the school. Unauthorized reproduction is strictly prohibited.</p>
            </div>
        </div>
    </div><!-- End container-fluid -->

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        // Automatically trigger print when requested via URL parameter
        document.addEventListener("DOMContentLoaded", function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('autoprint') === '1') {
                setTimeout(function() {
                    window.print();
                }, 1000);
            }
        });
    </script>
    
    <style>
        .signature-section {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
            page-break-inside: avoid;
        }
        
        .signature-box {
            width: 30%;
            text-align: center;
        }
        
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 40px;
            padding-top: 5px;
            text-align: center;
            font-weight: bold;
        }
        
        .signature-title {
            text-align: center;
            font-size: 9pt;
            color: #666;
        }
        
        .report-footer {
            border-top: 1px solid #ddd;
            padding-top: 20px;
            margin-top: 30px;
        }
        
        @media print {
            .report-footer {
                position: running(footer);
            }
            
            @page {
                @bottom-center {
                    content: element(footer);
                }
                margin-bottom: 2cm;
            }
        }
    </style>
    
    <?php if (isset($extra_js)) echo $extra_js; ?>
</body>
</html> 