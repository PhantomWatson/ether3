<?php
/**
 * @var \App\View\AppView $this
 */
?>
<div class="row" id="welcome">
    <div class="col-sm-2 col-sm-offset-2">
        <h2>
            Ether
        </h2>
        <h3>
            Thought Repository
        </h3>
    </div>
    <div class="col-sm-6" id="frontpage_random_thought">
        <?php if ($randomThought): ?>
            <h3>
                Random Thought:
            </h3>
            <span class="word">
                <?= $this->Html->link(
                    $randomThought->word,
                    [
                        'controller' => 'Thoughts',
                        'action' => 'word',
                        $randomThought->word,
                        '#' => 't'.$randomThought['id']
                    ]
                ) ?>
            </span>
            <br />
            <span class="thought_excerpt"><?= $randomThought->formatted_thought ?></span>
            <span class="byline">
                <?php if ($randomThought->anonymous): ?>
                    thought anonymously
                <?php else: ?>
                    thought by
                    <?= $this->element('colorbox', [
                        'color' => $randomThought->user['color'],
                        'anonymous' => $randomThought->anonymous
                    ]) ?>
                <?php endif; ?>
            </span>
        <?php endif; ?>
    </div>
</div>

<hr />

<div class="row">
    <div class="col-sm-8 col-sm-offset-2">
        <span class="glyphicon glyphicon-info-sign text-info"></span>
        <strong>New thing:</strong>
        Ever wondered what other people are wondering? If so, then I have some
        <strong>
            <?= $this->Html->link(
                'Questions',
                ['controller' => 'Thoughts', 'action' => 'questions']
            ) ?>
        </strong>
        for you.
        <div class="pull-right">
            <em>Love, Phantom</em>
        </div>
    </div>
</div>

<hr />

<div id="recent">
    <h2>
        Recent
    </h2>
    <?= $this->element('Thoughts'.DS.'recent') ?>
</div>
<div class="cloud <?= isset($_GET['animate']) ? 'animate_hide' : '' ?>" id="frontpage_cloud">
    <?php if (empty($cloud)): ?>
        <p>
            Sorry, we couldn't find any thoughts in the database.
            <br />That's probably a bad sign. :(
        </p>
    <?php else: ?>
        <?= $this->element('cloud', ['words' => $cloud, 'animate' => isset($_GET['animate'])]) ?>
        <?php if (isset($_GET['animate'])): ?>
            <script>
                var cloud = document.getElementById('frontpage_cloud');
                cloud.className = 'cloud animate_show';
            </script>
        <?php endif; ?>
    <?php endif; ?>
</div>