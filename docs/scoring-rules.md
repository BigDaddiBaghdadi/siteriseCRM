# MVP Scoring Rules

The first scoring pass is intentionally explainable. It should identify obvious website quality and conversion problems before any AI layer is added.

## Current Worker Signals

- HTTPS usage
- Page title presence
- Meta description presence
- Contact email presence
- Phone number presence
- Contact link presence
- Call-to-action language
- Approximate visible text length
- Basic CMS detection
- Analytics detection
- Chat widget detection

## Scores

### SEO

Starts at `100`.

- Missing title: `-30`
- Missing meta description: `-25`
- Non-HTTPS audited URL: `-15`

### Conversion

Starts at `100`.

- Missing obvious call to action: `-35`
- Missing contact link: `-25`
- Missing both email and phone: `-20`

### Content

Starts at `100`.

- Homepage text below 500 characters: `-35`

### Overall

Weighted blend:

```text
overall = seo * 0.30 + conversion * 0.45 + content * 0.25
```

### Redesign Opportunity

Current placeholder:

```text
redesign = clamp(100 - overall + 35)
```

The higher this score is, the stronger the redesign/update opportunity.

## Next Scoring Additions

- Browser-rendered mobile screenshot review
- Performance timing
- Accessibility basics
- Broken image detection
- Layout overflow detection
- Old framework/theme signals
- Forms and booking flow detection

