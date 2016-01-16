<div id="content_title">
    <h1>
        <?= $titleForLayout ?>
    </h1>
</div>

<p>
    This is a work in progress. This generates text based on thoughts that have been shared on Ether. Set the speed and the block length
    (smaller for more variation, larger for less variation) and let'r rip. The <em>Thinkin'</em> section at the bottom shows you how the
    process takes a block of N words and searches for a random word that someone, somewhere once wrote <em>after</em> that. The
    <em>Entropy Score</em> shows you how many times your text encountered a fork in the road where it could switch from one source
    thought to another.
    &nbsp; &nbsp;
    <em>Love, Phantom</em>
</p>

<div class="row">
    <section class="col-sm-4">
        <h2>
            Controls
        </h2>
        <div class="well">
            <div class="form-group row">
                <div class="col-sm-4">
                    Words per second:
                </div>
                <div class="col-sm-6">
                    <select id="speed" class="form-control">
                        <option value="0.5">
                            0.5
                        </option>
                        <?php for ($n = 1; $n <= 10; $n++): ?>
                            <option value="<?= $n ?>" <?= $n == 2 ? 'selected' : '' ?>>
                                <?= $n ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            <div class="form-group row">
                <div class="col-sm-4">
                    Block length:
                </div>
                <div class="col-sm-6">
                    <select id="blockLength" class="form-control">
                        <?php for ($n = 1; $n <= 5; $n++): ?>
                            <option value="<?= $n ?>" <?= $n == 2 ? 'selected' : '' ?>>
                                <?= $n ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            <br />
            <button id="stop" class="btn btn-default">Stop</button>
            <button id="start" class="btn btn-default">Start</button>
        </div>
    </section>

    <section class="col-sm-4 col-sm-offset-1">
        <h2>Thinkin':</h2>
        <div class="well">
            <p>
                Entropy score: <span id="entropyScore">0</span>
            </p>
            <div id="markovOptions"></div>
        </div>
    </section>
</div>

<section>
    <h2>Results:</h2>
    <p id="markovResults" class="well"></p>
</section>

<?php $this->Html->script('ether-markov', ['block' => 'script']); ?>
<?php $this->append('buffered_js'); ?>
    EtherMarkov.init();
<?php $this->end(); ?>