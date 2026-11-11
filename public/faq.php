<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="faqs";
$menu_item_id='faqs';
$title="faq_long";
include("header.php");

if ($proceed) {
    $query="SELECT * FROM ".table('faqs').", ".table('lang')."
            WHERE ".table('lang').".content_name=".table('faqs').".faq_id
            AND ".table('lang').".content_type='faq_answer'";
    $result=or_query($query);
    $answers=array();
    while ($line=pdo_fetch_assoc($result)) {
        $answers[$line['faq_id']]=$line;
    }

    $query="SELECT * FROM ".table('faqs').", ".table('lang')."
            WHERE ".table('lang').".content_name=".table('faqs').".faq_id
            AND ".table('lang').".content_type='faq_question'
            ORDER BY ".table('faqs').".evaluation DESC, ".table('lang').".".lang('lang');
    $result=or_query($query);

    if (!isset($_SESSION['vote'])) {
        $_SESSION['vote']=array();
    }

    $helpful_label=lang('this_faq_answered_my_question');

    echo '<div id="orsee-public-mobile-screen">';
    echo '<div class="orsee-public-faq-panel">';
    echo '<div class="orsee-faq-accordion">';

    $n_faqs=0;
    while ($line=pdo_fetch_assoc($result)) {
        $n_faqs++;
        $faq_id=(int)$line['faq_id'];
        $question_text=(isset($line[lang('lang')]) ? stripslashes($line[lang('lang')]) : '');
        $answer_text=(isset($answers[$faq_id]) && isset($answers[$faq_id][lang('lang')])) ? stripslashes($answers[$faq_id][lang('lang')]) : '';
        $question_html=htmlspecialchars($question_text,ENT_QUOTES,'UTF-8');
        $answer_html=helpers__render_richtext($answer_text);
        $voted=(isset($_SESSION['vote'][$faq_id]) && $_SESSION['vote'][$faq_id]);

        echo '<details class="orsee-faq-item box">';
        echo '<summary class="orsee-faq-summary">';
        echo '<span class="orsee-faq-question orsee-richtext">'.$question_html.'</span>';
        echo '<span class="orsee-faq-right">';
        echo '<span class="orsee-faq-rating"><i class="fa fa-thumbs-o-up"></i> <span class="orsee-faq-count" data-faq-count="'.$faq_id.'">'.(int)$line['evaluation'].'</span></span>';
        echo '<span class="orsee-faq-chevron"><i class="fa fa-angle-down"></i></span>';
        echo '</span>';
        echo '</summary>';

        echo '<div class="orsee-faq-answer">';
        echo '<div class="orsee-faq-answer-inner">';
        echo '<div class="orsee-faq-answer-text orsee-richtext">'.$answer_html.'</div>';
        echo '<div class="orsee-faq-actions">';
        if (!$voted) {
            echo '<button type="button" class="button orsee-public-btn orsee-faq-vote-button" data-faq-vote="'.$faq_id.'"><i class="fa fa-thumbs-o-up"></i>&nbsp;'.$helpful_label.'</button>';
        } else {
            echo '<button type="button" class="button orsee-public-btn orsee-faq-vote-button is-voted" disabled><i class="fa fa-check"></i>&nbsp;'.$helpful_label.'</button>';
        }
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</details>';
    }

    if ($n_faqs===0) {
        echo '<div class="orsee-callout orsee-callout-notice orsee-message-box"><div class="orsee-message-box-body">'.lang('no_faqs_found').'</div></div>';
    }

    echo '</div>';
    echo '</div>';
    echo '</div>';

    echo '<script type="text/javascript">
        document.addEventListener("click", function (event) {
            var btn = event.target.closest(".orsee-faq-vote-button");
            if (!btn) return;
            event.preventDefault();
            if (btn.disabled) return;

            var faqId = btn.getAttribute("data-faq-vote");
            if (!faqId) return;

            btn.disabled = true;
            var voteUrl = "faq_vote.php?eval=true&id=" + encodeURIComponent(faqId) + "&csrf_token=" + encodeURIComponent('.json_encode(csrf__get_token()).');
            fetch(voteUrl, { credentials: "same-origin" })
                .then(function () {
                    var countNode = document.querySelector("[data-faq-count=\'" + faqId + "\']");
                    if (countNode) {
                        var n = parseInt(countNode.textContent, 10);
                        if (!isNaN(n)) countNode.textContent = String(n + 1);
                    }
                    document.querySelectorAll(".orsee-faq-vote-button[data-faq-vote=\'" + faqId + "\']").forEach(function (voteBtn) {
                        voteBtn.classList.add("is-voted");
                        voteBtn.disabled = true;
                        voteBtn.removeAttribute("data-faq-vote");
                        voteBtn.innerHTML = "<i class=\"fa fa-check\"></i>&nbsp;".concat('.json_encode($helpful_label).');
                    });
                })
                .catch(function () {
                    btn.disabled = false;
                });
        });
    </script>';
}
include("footer.php");

?>
