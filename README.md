## Linkedin post content type
content type id: `linkedin_post`

field ids:
```
body - textarea
field_post_id - text
field_thumbnail - text
```
## Linkedin configurations
1. Create Linkedin app
2. Enable Advertising API on products page
3. Add authorized redirect URLs on OAuth 2.0 settings. You can get Drupal redirect URL on the Drupal settings form `/admin/config/services/linkedin`

## Instruction
1. Go to settings form `/admin/config/services/linkedin`
2. Set required fields.
3. Save configurations
4. Fetch Access Token
5. If token is fetched then you will find Token Expiration Date information on the settings form.
6. Create LinkedIn post content type with field ids provided above.
7. Run drush command `drush linkedin:company_fetch_all` - it will fetch 10 latest posts.
