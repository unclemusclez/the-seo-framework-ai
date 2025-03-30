jQuery(document).ready(function ($) {
  $("#tsf-ai-suggest").on("click", function () {
    // Use TSF’s field IDs
    var content =
      $("#autodescription-meta\\[doctitle\\]").val() ||
      $("#autodescription-meta\\[description\\]").val() ||
      "";
    if (!content) {
      alert("Please enter a title or description first.");
      return;
    }

    $.ajax({
      url: tsfAiSettings.ajaxurl,
      method: "POST",
      data: {
        action: "tsf_ai_get_suggestion",
        nonce: tsfAiSettings.nonce,
        content: content,
      },
      success: function (response) {
        if (response.success) {
          $("#tsf-ai-suggestion-result").html(
            "<p>Suggestion: " + response.data.suggestion + "</p>"
          );
        } else {
          $("#tsf-ai-suggestion-result").html(
            "<p>Error: " + response.data + "</p>"
          );
        }
      },
      error: function (xhr, status, error) {
        console.error("AJAX Error:", status, error);
        $("#tsf-ai-suggestion-result").html(
          "<p>Request failed. Check console for details.</p>"
        );
      },
    });
  });
});
