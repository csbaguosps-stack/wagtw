        </main>
    </div>

    <!-- Main AJAX Engine JS with Cache Buster -->
    <?php
        $mainJsFile = dirname(__DIR__, 3) . '/public/js/main.js';
        $mainJsVer = file_exists($mainJsFile) ? filemtime($mainJsFile) : time();
    ?>
    <script src="<?= BASEURL; ?>/js/main.js?v=<?= $mainJsVer ?>"></script>
</body>
</html>
