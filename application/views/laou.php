<html>
    <body>
        <form action='#' method='post'>
        <select name="usr_fk_home" id="cmbprofiles">
            <?php echo $retorno; ?>
        </select>
        <button type="submit" name="login" id="login" class="btn-primary-blue" formaction="fkhome">Login</button>
        <button type="submit" name="login_db" id="login_db" class="btn-primary-blue" formaction="fkhome_dbonly">Login DB</button>
        </form>
    </body>

</html>