To calibrate the README.md for proper Markdown rendering (especially on platforms like GitHub), we need to ensure consistent formatting, proper heading levels, code block syntax, and list structures. Below is the recalibrated version of the README.md with Markdown best practices applied:
markdown

# TSF AI Suggestions

A WordPress plugin that integrates AI-powered content suggestions into [The SEO Framework](https://wordpress.org/plugins/autodescription/) using an OpenAI-compatible endpoint (e.g., hosted with `llama.cpp`). This plugin enhances SEO workflows by suggesting improvements to titles and descriptions, with configurable settings and a user-friendly interface.

## Features

- **AI-Powered Suggestions**: Improve titles and descriptions using an OpenAI-compatible API.
- **Settings Integration**: Configure the AI endpoint, API key, and processing options within The SEO Framework's admin menu.
- **GUI Enhancements**: Adds a "Get AI Suggestions" button to TSF’s meta box for on-demand suggestions.
- **Checkbox Controls**: Enable/disable automatic AI processing for descriptions and titles via settings.
- **Customizable**: Works with local or remote `llama.cpp` servers or any OpenAI-compatible endpoint.

## Requirements

- WordPress 5.0 or higher
- [The SEO Framework](https://wordpress.org/plugins/autodescription/) plugin (version 5.0.0 or higher recommended)
- An OpenAI-compatible API endpoint (e.g., `llama.cpp` server running locally or remotely)

## Installation

1. **Download**:

   - Clone this repository or download the ZIP file.
   - Alternatively, copy the code into a folder named `tsf-ai-suggestions`.

2. **Install**:

   - Upload the `tsf-ai-suggestions` folder to your WordPress `wp-content/plugins/` directory.
   - Activate the plugin via the WordPress admin dashboard under **Plugins > Installed Plugins**.

3. **Set Up `llama.cpp` (Optional)**:
   - If using `llama.cpp`:
     - Compile it with server support: `make server`
     - Run the server: `./server -m path/to/model.gguf --host 0.0.0.0 --port 8080`
     - Ensure the endpoint (e.g., `http://localhost:8080/v1/completions`) is accessible.

## Configuration

1. **Access Settings**:

   - Navigate to **SEO > AI Suggestions** in the WordPress admin menu.

2. **Configure Options**:
   - **API Endpoint**: Set the URL of your OpenAI-compatible API (e.g., `http://localhost:8080/v1/completions` for `llama.cpp`).
   - **API Key**: Enter an optional API key if required by your endpoint.
   - **Max Tokens**: Define the maximum token limit for AI responses (default: 500).
   - **Temperature**: Adjust the creativity of suggestions (0-2, default: 0.7).
   - **Enable Description Suggestions**: Check to auto-process descriptions with AI.
   - **Enable Title Suggestions**: Check to auto-process titles with AI.
   - Save changes.

## Usage

### Automatic Suggestions

- If "Enable Description Suggestions" or "Enable Title Suggestions" is checked, the plugin automatically processes the respective fields on the front-end using the configured AI endpoint.
- Results are marked with `<ins>` (insertions) and `<del>` (deletions) tags for easy review.

### Manual Suggestions

1. **Edit a Post**:
   - Go to **Posts > Add New** or edit an existing post.
2. **Use the Meta Box**:
   - In The SEO Framework’s meta box (typically below the editor), find the "Get AI Suggestions" button.
   - Enter a title or description in the respective TSF fields.
   - Click the button to fetch and display AI suggestions.
3. **Apply Suggestions**:
   - Copy the suggested text (with `<ins>` and `<del>` tags) into the TSF fields manually if desired.

## File Structure

tsf-ai-suggestions/
├── assets/
│ └── js/
│ └── ai-suggestions.js # JavaScript for AJAX suggestion requests
├── includes/
│ ├── class-ai-suggestions.php # Core AI processing logic
│ └── class-settings.php # Settings and GUI integration
├── tsf-ai-suggestions.php # Main plugin file
└── README.md # This file

## Development

- **Customization**: Extend `class-ai-suggestions.php` to refine diff logic or add more AI features (e.g., sentence unification like Yoast).
- **Styling**: Add CSS to `assets/css/ai-suggestions.css` for custom meta box styling.
- **Debugging**: Check the WordPress debug log or browser console for API errors.

## Notes

- **Performance**: Local `llama.cpp` performance depends on your hardware. Use a small model for testing.
- **Security**: If exposing the API endpoint publicly, secure it with an API key and HTTPS.
- **TSF Compatibility**: Tested with TSF v5.0.0+. Adjust field IDs in `ai-suggestions.js` if they differ in your TSF version.

## Troubleshooting

- **API Not Responding**: Verify the endpoint URL and ensure the `llama.cpp` server is running.
- **No Suggestions**: Check the browser console for AJAX errors and confirm the nonce is valid.
- **Filter Not Applying**: Ensure checkboxes are enabled and settings are saved.

## License

This plugin is released under the [GPLv2 or later](https://www.gnu.org/licenses/gpl-2.0.html), compatible with WordPress.

## Contributions

Feel free to submit issues or pull requests on this repository to enhance functionality or fix bugs.
