# Chat Widget Styling Features

**Date:** 2025-11-04
**Status:** ✅ Implemented

## Overview

Users can now customize the appearance of the AI Persona Chat widget directly from the WordPress block editor. All styling options are available through intuitive controls in the block settings sidebar.

## Available Styling Options

### Color Settings

**1. Primary Color**
- Controls the color of user message bubbles
- Default: Blue (#1d4ed8)
- Applied via CSS variable: `--ai-persona-color-primary`

**2. Background Color**
- Controls the chat widget's background color
- Default: White (#ffffff)
- Applied via CSS variable: `--ai-persona-color-surface`

**3. Text Color**
- Controls the main text color throughout the widget
- Default: Dark gray (#222)
- Applied via CSS variable: `--ai-persona-color-text`

### Styling Options

**4. Border Radius**
- Range: 0-50 pixels
- Controls roundness of corners for the widget and message bubbles
- Default: 12px
- Set to 0 to use theme defaults
- Applied via CSS variable: `--ai-persona-radius-base`

**5. Max Width**
- Constrains the maximum width of the chat widget
- Accepts any CSS unit: `600px`, `80%`, `30rem`, etc.
- Leave empty for full width (100%)
- Applied via inline style: `max-width`

**6. Font Size**
- Controls the base font size for all text in the widget
- Accepts any CSS unit: `16px`, `1rem`, `14pt`, etc.
- Leave empty to inherit from theme
- Applied via CSS variable: `--ai-persona-font-size`

## How to Use

### In the Block Editor

1. **Add the AI Persona Chat block** to any page or post
2. **Open the block settings sidebar** (right panel)
3. **Navigate to the styling panels**:
   - **Color Settings** - Click to expand and choose colors
   - **Styling** - Click to expand for size and spacing options

### Available Panels

```
Block Settings Sidebar
├── Persona (select which persona to use)
├── Display (header visibility & title)
├── Color Settings
│   ├── Primary Color
│   ├── Background Color
│   └── Text Color
└── Styling
    ├── Border Radius (0-50px)
    ├── Max Width (e.g., 600px, 80%)
    └── Font Size (e.g., 16px, 1rem)
```

## Implementation Details

### CSS Variables Used

The implementation uses CSS custom properties (variables) for maximum flexibility:

```css
.ai-persona-chat {
  --ai-persona-color-primary: #1d4ed8;     /* User message color */
  --ai-persona-color-surface: #ffffff;      /* Widget background */
  --ai-persona-color-text: #222;            /* Text color */
  --ai-persona-radius-base: 12px;           /* Border radius */
  --ai-persona-font-size: 15px;             /* Base font size */
}
```

### Generated HTML

When a user customizes styling, the block outputs inline styles:

```html
<div class="ai-persona-chat"
     data-persona-id="209"
     data-show-header="true"
     data-header-title="Chat with persona"
     style="--ai-persona-color-primary: #e11d48; --ai-persona-color-surface: #fef2f2; max-width: 600px">
  <!-- Widget content -->
</div>
```

### Attribute Storage

All styling options are stored as block attributes in `block.json`:

```json
{
  "primaryColor": {"type": "string", "default": ""},
  "backgroundColor": {"type": "string", "default": ""},
  "textColor": {"type": "string", "default": ""},
  "borderRadius": {"type": "number", "default": 0},
  "maxWidth": {"type": "string", "default": ""},
  "fontSize": {"type": "string", "default": ""}
}
```

## Files Modified

### 1. Block Definition (`blocks/ai-persona-chat/block.json`)
- Added 6 new styling attributes
- All attributes have safe defaults (empty strings or 0)

### 2. Block Editor Script (`blocks/ai-persona-chat/index.js`)
- Added `PanelColorSettings` for color pickers
- Added `RangeControl` for border radius slider
- Added `TextControl` fields for max-width and font-size
- New panels: "Color Settings" and "Styling"

### 3. Render Callback (`includes/frontend/chat-widget.php`)
- `render_chat_widget()` function enhanced to accept styling parameters
- Builds inline CSS variables and styles dynamically
- `render_chat_block()` function passes all styling attributes
- Color sanitization via `sanitize_hex_color()`
- Dimension sanitization via `sanitize_text_field()` and `absint()`

## Example Use Cases

### Use Case 1: Brand Matching
A company wants the chat widget to match their brand colors:
```
Primary Color: #e11d48 (company red)
Background: #fef2f2 (light pink)
Text Color: #881337 (dark red)
Border Radius: 20px
```

### Use Case 2: Minimal Design
A minimalist site wants clean, sharp edges:
```
Primary Color: #000000 (black)
Background: #f5f5f5 (light gray)
Border Radius: 0px
Max Width: 500px
```

### Use Case 3: Large Text for Accessibility
An accessible site needs larger text:
```
Font Size: 18px
Border Radius: 8px
Max Width: 700px
```

### Use Case 4: Compact Sidebar Widget
A widget in a sidebar needs to be narrow:
```
Max Width: 300px
Font Size: 14px
Border Radius: 16px
```

## Shortcode Support

Styling options also work with the shortcode:

```php
[ai_persona_chat
  id="209"
  primary_color="#e11d48"
  background_color="#fef2f2"
  border_radius="20"
  max_width="600px"
  font_size="16px"
]
```

## Filter Hook

Developers can programmatically override styling via the `ai_persona_chat_attributes` filter:

```php
add_filter('ai_persona_chat_attributes', function($atts) {
  // Force all chat widgets to use brand colors
  $atts['primary_color'] = '#e11d48';
  $atts['background_color'] = '#fef2f2';
  return $atts;
});
```

## Theme Compatibility

- **CSS Variables**: Modern browsers support CSS custom properties (95%+ coverage)
- **Fallbacks**: Default styles apply if custom values not provided
- **Theme Overrides**: Themes can override via higher specificity CSS
- **No Conflicts**: Uses namespaced variables (`--ai-persona-*`)

## Testing Checklist

When testing styling customization:

- [ ] Color pickers appear in "Color Settings" panel
- [ ] Colors update in real-time (if theme supports)
- [ ] Border radius slider works (0-50px range)
- [ ] Max width field accepts various units (px, %, rem)
- [ ] Font size field accepts various units (px, rem, pt)
- [ ] Empty values fall back to defaults gracefully
- [ ] Styles persist after saving and reloading page
- [ ] Frontend matches editor styling
- [ ] Works with both block and shortcode
- [ ] CSS variables apply correctly in browser inspector

## Browser Support

- ✅ Chrome/Edge (90+)
- ✅ Firefox (85+)
- ✅ Safari (14+)
- ✅ Mobile browsers (iOS 14+, Android Chrome)
- ⚠️ IE11 (CSS variables not supported - falls back to defaults)

## Future Enhancements (Optional)

Potential additions for future versions:

1. **Typography Presets**: Predefined font combinations
2. **Color Schemes**: Pre-built light/dark mode toggles
3. **Spacing Controls**: Padding and margin adjustments
4. **Animation Options**: Fade in, slide in effects
5. **Message Bubble Styles**: Different shapes (rounded, sharp, pill)
6. **Header Styling**: Separate controls for header appearance
7. **Button Customization**: Send button color and style
8. **Shadow Controls**: Box shadow intensity
9. **Style Presets**: One-click professional themes
10. **Global Styles**: Site-wide default styling

## Security

All styling inputs are properly sanitized:
- Colors: `sanitize_hex_color()`
- Numbers: `absint()`
- Text dimensions: `sanitize_text_field()`
- Escaped output: `esc_attr()`

No XSS vulnerabilities introduced.

## Performance

- **Minimal Overhead**: Only adds inline styles when customized
- **No Extra HTTP Requests**: Styles applied directly to HTML
- **No JavaScript Required**: Pure CSS implementation
- **Cache-Friendly**: Works with all caching plugins

## Documentation for Users

For end users, add this to your theme documentation:

> ### Customizing the AI Chat Widget
>
> 1. Edit the page containing the chat widget
> 2. Click on the chat widget block
> 3. Open the settings panel on the right
> 4. Expand "Color Settings" to change colors
> 5. Expand "Styling" to adjust size and spacing
> 6. Click "Update" to save your changes
>
> All changes are live immediately on your published page!
