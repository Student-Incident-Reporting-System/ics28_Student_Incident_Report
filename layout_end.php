    </div><!-- /.page-body -->
</div><!-- /#main -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script>
    // Auto-dismiss alerts after 4 s
    setTimeout(() => {
        document.querySelectorAll('.alert-dismissible').forEach(el => {
            bootstrap.Alert.getOrCreateInstance(el)?.close();
        });
    }, 4000);
</script>
<?php if (!empty($extraJS)) echo $extraJS; ?>
</body>
</html>
