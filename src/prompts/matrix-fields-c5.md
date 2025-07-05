# Matrix Fields (Craft 5+)

In Craft CMS 5, Matrix Blocks are implemented as Entries, with their structure defined by global Entry Types. This allows Entry Types to be reused across multiple Matrix Fields, enhancing consistency.

## Key Concepts

- Matrix Blocks → Entries: Blocks within a Matrix Field are stored as nested Entries.
- Block Types → Entry Types: Block definitions are global Entry Types, not defined within individual Matrix Fields.

## Preconditions

Before creating a Matrix Field:
1. Check existing Fields: Identify reusable global fields.
2. Check existing Entry Types: Verify if suitable Entry Types already exist or create new ones as needed.

## Step-by-Step Matrix Field Creation

1. Prepare Fields:
    - Confirm required fields exist globally.
    - Create new fields if necessary.
2. Prepare Entry Types:
    - Review global Entry Types.
    - Create new Entry Types if needed, defining their field layouts clearly.
3. Create Matrix Field:
    - In Craft CMS, navigate to Settings → Fields.
    - Create a new field of type Matrix.
    - Select existing Entry Types to use as blocks within this Matrix Field.
    - Configure Matrix-specific settings (min/max entries, propagation method, view mode).
    - Save the Matrix Field.
4. Post-Creation Adjustments:
    - Fields within Entry Types can be adjusted globally at any time under Settings → Entry Types.
    - Changes are immediately applied wherever the Entry Type is used.

## Field Groups

- Craft CMS 5 no longer uses field groups. All fields exist in a single global list.
- Use descriptive field naming conventions for clarity and maintainability.
