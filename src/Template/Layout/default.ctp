<?php
/**
 * @var \App\View\AppView $this
 */
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <?= $this->Html->charset() ?>
        <title>
            <?php
                $title = 'Ether - Thought Repository';
                if (isset($titleForLayout) && !isset($title_for_layout)) {
                    $title_for_layout = $titleForLayout;
                }
                if (isset($title_for_layout) && $title_for_layout !== '') {
                    $title = 'Ether :: '.$title_for_layout;
                }
                echo $title;
            ?>
        </title>
        <link rel="dns-prefetch" href="//ajax.googleapis.com" />
        <link rel="icon" type="image/png" href="/img/favicon.png" />
        <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css">
        <?php
            echo $this->Html->css('style');
            echo $this->fetch('meta');
        ?>
        <meta name="title" content="<?= $title ?>" />
        <meta name="description" content="Ether: An experimental freeform thought repository. What's on YOUR mind?" />
        <meta name="author" content="Phantom Watson" />
        <meta name="language" content="en" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
    </head>
    <body>
        <?php
            echo $this->element('header');
            echo $this->fetch('overlay');
        ?>

        <div id="content_outer">
            <div id="content">
                <?= $this->element('flash_messages') ?>
                <?= $this->fetch('content') ?>
            </div>
        </div>

        <?= $this->element('footer') ?>

        <!-- Grab Google CDN's jQuery, with a protocol relative URL; fall back to local if offline -->
        <script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
        <script>window.jQuery || document.write('<script src="/js/jquery-1.9.1.min.js"><\/script>')</script>

        <?php
            $scriptFile = $debug ? 'script.concat.js' : 'script.concat.min.js';
            echo $this->Html->script($scriptFile);
            echo $this->fetch('script');
            echo $this->element('analytics');
        ?>

        <script>
            $(document).ready(function () {
                scroll.init();
                <?= $this->fetch('buffered_js') ?>
            });
        </script>
    </body>
</html>
