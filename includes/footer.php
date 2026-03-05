        </div> <!-- End Container -->
    </div> <!-- End Content -->
</div> <!-- End Wrapper -->

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery (required for DataTables) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Custom App Logic -->
<script>
    $(document).ready(function () {
        // Sidebar Toggle
        $('#sidebarCollapse').on('click', function () {
            $('#sidebar').toggleClass('active');
            if ($('#sidebar').hasClass('active')) {
                $('#sidebar').css('margin-left', '-250px');
                $('#content').css('width', '100%');
                $('#content').css('margin-left', '0');
            } else {
                $('#sidebar').css('margin-left', '0');
                $('#content').css('width', 'calc(100% - 250px)');
                $('#content').css('margin-left', '250px');
            }
        });

        // Initialize any DataTables
        $('.datatable').DataTable({
            pageLength: 25,
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search records..."
            }
        });
    });
</script>
</body>
</html>
