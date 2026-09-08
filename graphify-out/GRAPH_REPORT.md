# Graph Report - سامانه-جامع-خدمات-شهروندی-و-پیشخوان-هوشمند (3)  (2026-09-08)

## Corpus Check
- cluster-only mode — file stats not available

## Summary
- 229 nodes · 432 edges · 13 communities (11 shown, 1 thin omitted)
- Extraction: 100% EXTRACTED · 0% INFERRED · 0% AMBIGUOUS
- Token cost: 0 input · 0 output

## Community Hubs (Navigation)
- Community 0
- Community 1
- Community 2
- Community 3
- Community 4
- Community 5
- Community 6
- Community 7
- Community 8
- Community 9
- Community 10
- Community 11

## God Nodes (most connected - your core abstractions)
1. `CaseRequest` - 17 edges
2. `PishkhanOffice` - 17 edges
3. `CitizenService` - 16 edges
4. `CitizenProfile` - 15 edges
5. `compilerOptions` - 15 edges
6. `ConsultationAdvisor` - 9 edges
7. `ChatMessage` - 9 edges
8. `Appointment` - 7 edges
9. `WalletTransaction` - 7 edges
10. `CATEGORIES` - 7 edges

## Surprising Connections (you probably didn't know these)
- `ServicesCatalogViewProps` --references--> `CitizenService`  [EXTRACTED]
  src/components/ServicesCatalogView.tsx → src/types.ts
- `NavbarProps` --references--> `CaseRequest`  [EXTRACTED]
  src/components/Navbar.tsx → src/types.ts
- `AdvisorRegistrationModalProps` --references--> `ConsultationAdvisor`  [EXTRACTED]
  src/components/AdvisorRegistrationModal.tsx → src/types.ts
- `BusinessSubscriptionModalProps` --references--> `BusinessSubscriptionPlan`  [EXTRACTED]
  src/components/BusinessSubscriptionModal.tsx → src/types.ts
- `LegalDelegationModalProps` --references--> `LegalDelegation`  [EXTRACTED]
  src/components/LegalDelegationModal.tsx → src/types.ts

## Import Cycles
- None detected.

## Communities (13 total, 1 thin omitted)

### Community 0 - "Community 0"
Cohesion: 0.12
Nodes (27): AdvisorRegistrationModal(), AdvisorRegistrationModalProps, BusinessSubscriptionModal(), BusinessSubscriptionModalProps, ConsultationDetailModal(), ConsultationDetailModalProps, ConsultationHubView(), ConsultationHubViewProps (+19 more)

### Community 1 - "Community 1"
Cohesion: 0.11
Nodes (23): App(), CategoryFilter(), CategoryFilterProps, CtaSlider(), CtaSliderProps, LegalDelegationModal(), LegalDelegationModalProps, OfficeLoginView() (+15 more)

### Community 2 - "Community 2"
Cohesion: 0.07
Nodes (28): autoprefixer, esbuild, vite, devDependencies, autoprefixer, esbuild, tailwindcss, tsx (+20 more)

### Community 3 - "Community 3"
Cohesion: 0.13
Nodes (21): OfficeLoginViewProps, OfficePortalViewProps, getOfficeStatus(), MAP_THEMES, OfficesMap(), OfficesMapProps, USER_LOCATION, ServiceCardProps (+13 more)

### Community 4 - "Community 4"
Cohesion: 0.09
Nodes (23): dotenv, express, @google/genai, leaflet, lucide-react, motion, dependencies, dotenv (+15 more)

### Community 5 - "Community 5"
Cohesion: 0.11
Nodes (18): DOM, DOM.Iterable, ES2022, compilerOptions, allowImportingTsExtensions, allowJs, experimentalDecorators, isolatedModules (+10 more)

### Community 6 - "Community 6"
Cohesion: 0.17
Nodes (12): AppointmentModal(), AppointmentModalProps, CaseTrackingView(), CaseTrackingViewProps, MessagesView(), MessagesViewProps, ProfileSubPage, UserProfileView() (+4 more)

### Community 7 - "Community 7"
Cohesion: 0.20
Nodes (11): CitizenLoginView(), CitizenLoginViewProps, REGISTERED_USERS_DB, Header(), HeaderProps, WalletCard(), WalletCardProps, WalletDetailsView() (+3 more)

### Community 8 - "Community 8"
Cohesion: 0.13
Nodes (14): DeliveryDocType, DeskCitizenReview, DeskTab, DocumentDeliveryRequest, INITIAL_DELIVERY_REQUESTS, INITIAL_IN_PERSON_APPOINTMENTS, INITIAL_OFFICE_REVIEWS, InPersonAppointment (+6 more)

### Community 9 - "Community 9"
Cohesion: 0.29
Nodes (5): LiquidTabItem, Navbar(), NavbarProps, NavTab, Rect

### Community 10 - "Community 10"
Cohesion: 0.33
Nodes (5): *.jpeg, *.jpg, *.png, *.svg, *.webp

## Knowledge Gaps
- **77 isolated node(s):** `ConsultationHubViewProps`, `AdvisorRatingBreakdown`, `AdvisorRegistrationForm`, `CaseStatus`, `CaseTimelineStep` (+72 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 84 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **1 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `dependencies` connect `Community 4` to `Community 2`?**
  _High betweenness centrality (0.034) - this node is a cross-community bridge._
- **Why does `CitizenService` connect `Community 3` to `Community 0`, `Community 1`?**
  _High betweenness centrality (0.018) - this node is a cross-community bridge._
- **What connects `ConsultationHubViewProps`, `AdvisorRatingBreakdown`, `AdvisorRegistrationForm` to the rest of the system?**
  _77 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Community 0` be split into smaller, more focused modules?**
  _Cohesion score 0.12299465240641712 - nodes in this community are weakly interconnected._
- **Should `Community 1` be split into smaller, more focused modules?**
  _Cohesion score 0.11174242424242424 - nodes in this community are weakly interconnected._
- **Should `Community 2` be split into smaller, more focused modules?**
  _Cohesion score 0.06896551724137931 - nodes in this community are weakly interconnected._
- **Should `Community 3` be split into smaller, more focused modules?**
  _Cohesion score 0.12698412698412698 - nodes in this community are weakly interconnected._