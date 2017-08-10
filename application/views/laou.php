<html>
    <head>
        <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
        <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
        <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    </head>
    <body>
        <select name="cmbprofiles" id="cmbprofiles">
            <?php echo $retorno; ?>
        </select>
        <button id='btnLogin'>Login</button>
    </body>

    <script>
        $('#btnLogin').click(function(){
            var form_data = {
                usr_fk_home: $('#cmbprofiles').val()
            };

            var resp = $.ajax({
                url: 'app/fkhome',
                type: 'POST',
                data: form_data,
                global: false,
                async:false,
                success: function(msg) { 
             
                }
            });
        });
    </script>
</html>