
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/controleonline/api-platform-people/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/controleonline/api-platform-people/?branch=master)

# people


`composer require controleonline/people:dev-master`

Add Service import:
config\services.yaml

```yaml
imports:
    - { resource: "../modules/controleonline/orders/people/services/people.yaml" }    
```

## Nested user serialization

Broad `People` reads must not expose nested `User` credentials or identifiers.

Current behavior:
- broad `people:read` responses no longer serialize nested `User` credentials from the related person record
- public or broad `People` reads must not be used as a side channel for `username`, `apiKey`, or other sensitive `User` fields
- sensitive user management stays on the dedicated `users` resource and its guarded service path

Validation:
- focused coverage lives in `tests/Unit/Entity/PeopleSerializationGroupsTest.php`
- the branch workflow `Pull Request Checks` is the canonical automated evidence for this serialization rule in review branches
