---
name: Academic Excellence Portal
colors:
  surface: '#f7f9fb'
  surface-dim: '#d8dadc'
  surface-bright: '#f7f9fb'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f2f4f6'
  surface-container: '#eceef0'
  surface-container-high: '#e6e8ea'
  surface-container-highest: '#e0e3e5'
  on-surface: '#191c1e'
  on-surface-variant: '#44474d'
  inverse-surface: '#2d3133'
  inverse-on-surface: '#eff1f3'
  outline: '#75777e'
  outline-variant: '#c5c6cd'
  surface-tint: '#515f78'
  primary: '#000000'
  on-primary: '#ffffff'
  primary-container: '#0d1c32'
  on-primary-container: '#76849f'
  inverse-primary: '#b9c7e4'
  secondary: '#705d00'
  on-secondary: '#ffffff'
  secondary-container: '#fcd400'
  on-secondary-container: '#6e5c00'
  tertiary: '#000000'
  on-tertiary: '#ffffff'
  tertiary-container: '#410007'
  on-tertiary-container: '#f1414d'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#d6e3ff'
  primary-fixed-dim: '#b9c7e4'
  on-primary-fixed: '#0d1c32'
  on-primary-fixed-variant: '#39475f'
  secondary-fixed: '#ffe16d'
  secondary-fixed-dim: '#e9c400'
  on-secondary-fixed: '#221b00'
  on-secondary-fixed-variant: '#544600'
  tertiary-fixed: '#ffdad8'
  tertiary-fixed-dim: '#ffb3b1'
  on-tertiary-fixed: '#410007'
  on-tertiary-fixed-variant: '#92001c'
  background: '#f7f9fb'
  on-background: '#191c1e'
  surface-variant: '#e0e3e5'
typography:
  display-lg:
    fontFamily: Inter
    fontSize: 48px
    fontWeight: '700'
    lineHeight: 56px
    letterSpacing: -0.02em
  headline-lg:
    fontFamily: Inter
    fontSize: 32px
    fontWeight: '600'
    lineHeight: 40px
  headline-lg-mobile:
    fontFamily: Inter
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
  title-md:
    fontFamily: Inter
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
  body-lg:
    fontFamily: Inter
    fontSize: 18px
    fontWeight: '400'
    lineHeight: 28px
  body-md:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  label-md:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '500'
    lineHeight: 20px
    letterSpacing: 0.01em
  label-sm:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '600'
    lineHeight: 16px
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  base: 8px
  container-max: 1280px
  gutter: 24px
  margin-mobile: 16px
  margin-desktop: 32px
  stack-sm: 12px
  stack-md: 24px
  stack-lg: 48px
---

## Brand & Style

The design system is engineered for the high-stakes environment of academic assessment. It balances a prestigious, institutional feel with the friction-free clarity required for digital testing. The brand personality is authoritative yet supportive, ensuring students feel a sense of significance while maintaining focus on the content.

The visual style follows a **Corporate / Modern** aesthetic, utilizing a disciplined structural grid and clear information hierarchy. By prioritizing legibility and cognitive ease, the system minimizes "test-taking anxiety" through organized card layouts, purposeful use of whitespace, and a high-contrast color palette that directs attention to critical exam actions.

## Colors

The color palette is functionally driven to distinguish between navigation, information, and urgent action.

*   **Primary (#0A192F):** Deep Navy/Black is used for structural headers, primary text, and formal UI elements to establish authority and focus.
*   **Secondary (#FFD700):** Gold is applied exclusively for accents, active states, and highlighting progress. It serves as a visual reward and a signifier of achievement.
*   **Tertiary (#E63946):** This high-energy Red is reserved strictly for Call-to-Action (CTA) buttons, critical alerts, and time-sensitive warnings (e.g., "Submit Exam" or "Time Remaining" alerts).
*   **Neutrals:** The background remains a pure White (#FFFFFF) to maximize contrast. Off-white and soft grays are used for card surfaces and inactive states to prevent visual fatigue during long testing sessions.

## Typography

This design system utilizes **Inter** for its exceptional legibility and systematic weights. The typography is designed for sustained reading of complex question prompts.

The scale is strictly hierarchical. **Headlines** use heavier weights (Semi-Bold/Bold) to anchor pages, while **Body** text uses a generous line-height to improve scanning speeds. For CBT interfaces, "Body-LG" is the default for question stems to ensure accessibility across all age groups. **Labels** are used for metadata, such as question numbers and timers, often utilizing a slightly tighter tracking for a technical, precise appearance.

## Layout & Spacing

This design system employs a **Fixed Grid** for desktop to maintain optimal line lengths for reading, while transitioning to a **Fluid Grid** for mobile devices.

*   **Grid System:** A 12-column grid is used for desktop dashboards, while a 1-column stack is preferred for the active exam environment to eliminate side-panel distractions.
*   **Spacing Rhythm:** An 8px linear scale ensures consistency. Gutters are set to 24px to provide ample breathing room between question modules and navigation sidebars.
*   **Responsive Strategy:** On mobile, margins reduce to 16px. Interactive elements like "Next" and "Previous" buttons move to a fixed bottom navigation bar for thumb-friendly interaction.

## Elevation & Depth

Visual hierarchy is established through a combination of **Tonal Layers** and **Ambient Shadows**. 

The base background is at the lowest elevation (Level 0). Exam content resides on cards at Level 1, featuring a subtle, diffused shadow (0px 4px 12px rgba(10, 25, 47, 0.05)) to separate the question from the workspace. Interactive elements like active text fields or hovered buttons move to Level 2 with a slightly more pronounced shadow to indicate clickability. 

Navigation headers are fixed at the top of the viewport with a low-opacity border bottom (#E2E8F0) rather than a heavy shadow, maintaining a clean and modern top-down light source.

## Shapes

The design system uses a **Rounded** shape language (8px to 12px) to soften the clinical nature of a test portal. 

Standard components like input fields and buttons utilize a 8px radius. Larger containers, such as question cards and modal dialogs, use a 12px radius. This consistent curvature creates a approachable, "software-as-a-service" feel rather than an antiquated institutional one. Progress bars and status badges use fully rounded (pill-shaped) caps to differentiate them from actionable rectangular buttons.

## Components

The design system requires highly specialized components for the CBT experience:

*   **Cards:** Question containers must have a clear 1px border (#E2E8F0) and an 8-12px corner radius. They should feature internal padding of 32px for desktop.
*   **Buttons:** 
    *   *Primary (Navy):* Used for standard navigation (e.g., Next/Back).
    *   *CTA (Red):* High-contrast, used only for "End Exam" or "Submit".
    *   *Ghost:* Used for secondary actions like "Flag for Review."
*   **Progress Bars:** 12px height, using a Gold (#FFD700) fill against a light gray track.
*   **Interactive Tables:** Used for the "Question Map" view. Rows should have hover states (#F8FAFC) and use badges to indicate "Attempted," "Not Attempted," or "Flagged."
*   **Badges:** Small, pill-shaped indicators for "Timer," "Section Name," and "Question Type," using low-saturation backgrounds with high-saturation text for readability.
*   **Input Fields:** Large tap targets (min 48px height) for multiple-choice selections, featuring a clear Navy border on selection.