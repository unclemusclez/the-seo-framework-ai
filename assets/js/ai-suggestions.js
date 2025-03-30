jQuery(document).ready(function ($) {
  $("#tsf-ai-suggest").on("click", function () {
    var content =
      $("#tsf-title-input").val() || $("#tsf-description-input").val() || "";
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
      error: function () {
        $("#tsf-ai-suggestion-result").html(
          "<p>Request failed. Check your API settings.</p>"
        );
      },
    });
  });
});
