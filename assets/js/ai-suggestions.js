jQuery(document).ready(function ($) {
  if (!$("#tsf-ai-suggest").length) {
    return;
  }

  $("#tsf-ai-suggest").on("click", function () {
    var content =
      $("#autodescription-meta\\[doctitle\\]").val() ||
      $("#autodescription-meta\\[description\\]").val() ||
      "";

    var $result = $("#tsf-ai-suggestion-result");
    if (!content) {
      $result.html(
        '<p class="tsf-ai-error">Please enter a title or description first.</p>'
      );
      return;
    }

    $result.html('<p class="tsf-ai-loading">Generating suggestion...</p>');
    $(this).prop("disabled", true);

    $.ajax({
      url: tsfAiSettings.ajaxurl,
      method: "POST",
      data: {
        action: "tsf_ai_get_suggestion",
        nonce: tsfAiSettings.nonce,
        content: content,
      },
      success: function (response) {
        $("#tsf-ai-suggest").prop("disabled", false);

        if (response.success) {
          var suggestion = $("<div>").text(response.data.suggestion).html();
          var note = tsfAiSettings.isAiGenerationEnabled
            ? '<p class="tsf-ai-note">Note: AI descriptions are automatically generated for this page.</p>'
            : "";
          $result.html(
            '<p class="tsf-ai-suggestion"><strong>Suggestion:</strong> ' +
              suggestion +
              "</p>" +
              note
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
