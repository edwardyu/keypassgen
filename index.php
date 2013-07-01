
<?php require_once 'header.php'; ?>
      <div class ="offset3 span6">
          <h1>Password Generator</h1>
          <p class="lead">Type in a keyword to generate a password from, or generate a random one.</p>

          
          <?php
          if (!empty($_GET['q']) || !empty($_GET['random'])) {
              require_once 'functions.php';
              /*
                print "Hello, World!<br />";
                print "Generating random password...<br />";
                $password = generate_random_password();
                print "Your password is: " . $password . "<br />";
                print "Making your password secure...<br />";
                print secure_password($password);
               * 
               */
              if(!empty($_GET['random']))
                  $keyword = generate_random_password();
              else
                  $keyword = urldecode(trim($_GET['q']));
              
              $info = get_associations($keyword);
              $description = $info[0];
              $words = extract_keywords($info[1]);
              $r = rand(0,1);
              print "<img src='http://mebe.co/" . urlencode($words[$r]) . "jpeg' class = 'img-rounded'/><br /><br />";
              print "<p>Generating password from keyword <strong>$keyword</strong>...</p>";

              print "<p>Hmm, <strong>$keyword</strong>? How about $description?</p>";
              
//print_r($words);
              print "<p>This brings to mind <strong>$words[0]</strong> " . "and <strong>$words[1]</strong> </p>";

              $words[$r] = str_replace(' ', '', $words[$r]);
              print "<p class = 'text-success'>Your password is: <code>" . secure_password($words[$r]) . "</code></p>";
              print '<a class="btn btn-primary" href="index.php"><i class="icon-refresh icon-white"></i> Generate more</a>';
          } else {
              ?>

              <form method = "GET" action = "index.php">
                  <input type = "text" name = "q" class = "span6" />
                  <input type = "submit" value = "Generate" class = "btn btn-primary"/>
                  <input type = "submit" name = "random" value = "Randomize" class ="btn btn-success"/>
              </form> 
    <?php
}
?>


      </div>
<?php require_once 'footer.php'; ?>