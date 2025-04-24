jQuery(document).ready(function ($) {
  // Exit if the button doesn’t exist
  if (!$("#tsf-ai-suggest").length) {
    return;
  }

  $("#tsf-ai-suggest").on("click", function () {
    // Get content from TSF title or description fields
    var content =
      $("#autodescription-meta\\[doctitle\\]").val() ||
      $("#autodescription-meta\\[description\\]").val() ||
      "";

    // Show error if no content is provided
    var $result = $("#tsf-ai-suggestion-result");
    if (!content) {
      $result.html(
        '<p class="tsf-ai-error">Please enter a title or description first.</p>'
      );
      return;
    }

    // Show loading state
    $result.html('<p class="tsf-ai-loading">Generating suggestion...</p>');
    $(this).prop("disabled", true); // Disable button during request

    $.ajax({
      url: tsfAiSettings.ajaxurl,
      method: "POST",
      data: {
        action: "tsf_ai_get_suggestion",
        nonce: tsfAiSettings.nonce,
        content: content,
      },
      success: function (response) {
        // Re-enable button
        $("#tsf-ai-suggest").prop("disabled", false);

        if (response.success) {
          // Escape suggestion to prevent XSS
          var suggestion = $("<div>").text(response.data.suggestion).html();
          $result.html(
            '<p class="tsf-ai-suggestion"><strong>Suggestion:</strong> ' +
              suggestion +
              "</p>"
          );
        } else {
          $result.html(
            '<p class="tsf-ai-error">Error: ' +
              (response.data || "Unknown error") +
              "</p>"
          );
        }
      },
      error: function (xhr, status, error) {
        // Re-enable button
        $("#tsf-ai-suggest").prop("disabled", false);

        var errorMessage = "Request failed. Please try again.";
        if (xhr.status === 403) {
          errorMessage = "Permission denied. Contact an administrator.";
        } else if (xhr.status === 0) {
          errorMessage = "Network error. Check your connection.";
        }

        $result.html('<p class="tsf-ai-error">' + errorMessage + "</p>");

        if (typeof console !== "undefined") {
          console.log("TSF AI Suggestions AJAX Error:", status, error);
        }
      },
    });
  });
});
