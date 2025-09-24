<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Knowledge Vault - {{ $trigger->name ?? 'Prompt' }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/vuetify@3.4.0/dist/vuetify.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@7.0.96/css/materialdesignicons.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
    </style>
</head>
<body>
    <div id="app">
        <v-app>
            <v-main>
                <v-container class="pa-6">
                    <prompt-form
                        :delivery="{{ json_encode($delivery) }}"
                        :trigger="{{ json_encode($trigger) }}"
                        :prompt="{{ json_encode($prompt) }}"
                    ></prompt-form>
                </v-container>
            </v-main>
        </v-app>
    </div>

    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/vuetify@3.4.0/dist/vuetify.min.js"></script>
    <script>
        const { createApp } = Vue
        const { createVuetify } = Vuetify

        const vuetify = createVuetify({
            theme: {
                defaultTheme: 'dark'
            }
        })

        const PromptForm = {
            props: ['delivery', 'trigger', 'prompt'],
            data() {
                return {
                    loading: false,
                    formData: {
                        knowledge_context: '',
                        technical_details: '',
                        lessons_learned: '',
                        future_considerations: ''
                    },
                    completed: false
                }
            },
            template: `
                <v-card v-if="!completed" class="mx-auto elevation-12" max-width="800">
                    <v-card-title class="text-h4 pa-6 text-center">
                        <v-icon size="large" class="mr-3" color="primary">mdi-lightbulb</v-icon>
                        @{{ trigger.name || 'Knowledge Capture' }}
                    </v-card-title>

                    <v-card-subtitle class="text-center pb-4">
                        <v-chip color="primary" variant="outlined" class="mr-2">
                            <v-icon start>mdi-source-repository</v-icon>
                            @{{ getRepoName() }}
                        </v-chip>
                        <v-chip color="secondary" variant="outlined">
                            <v-icon start>mdi-source-commit</v-icon>
                            @{{ delivery.github_event_type }}
                        </v-chip>
                    </v-card-subtitle>

                    <v-divider></v-divider>

                    <v-card-text class="pa-6">
                        <div class="mb-6">
                            <v-alert
                                type="info"
                                variant="tonal"
                                class="mb-4"
                                icon="mdi-information"
                            >
                                Help us capture knowledge about your recent changes! This information will be valuable for the team.
                            </v-alert>
                        </div>

                        <v-form @submit.prevent="submitResponse" class="space-y-4">
                            <v-textarea
                                v-model="formData.knowledge_context"
                                label="What changes did you make and why?"
                                placeholder="Describe the context and reasoning behind your changes..."
                                variant="outlined"
                                rows="3"
                                required
                                class="mb-4"
                            ></v-textarea>

                            <v-textarea
                                v-model="formData.technical_details"
                                label="Technical implementation details"
                                placeholder="Any technical details worth noting (architecture decisions, dependencies, etc.)..."
                                variant="outlined"
                                rows="3"
                                class="mb-4"
                            ></v-textarea>

                            <v-textarea
                                v-model="formData.lessons_learned"
                                label="Challenges faced or lessons learned"
                                placeholder="What challenges did you encounter? What would you do differently?"
                                variant="outlined"
                                rows="2"
                                class="mb-4"
                            ></v-textarea>

                            <v-textarea
                                v-model="formData.future_considerations"
                                label="Future considerations"
                                placeholder="Anything the team should know for future development..."
                                variant="outlined"
                                rows="2"
                                class="mb-4"
                            ></v-textarea>
                        </v-form>
                    </v-card-text>

                    <v-divider></v-divider>

                    <v-card-actions class="pa-6">
                        <v-btn
                            variant="outlined"
                            @click="skipForm"
                            class="mr-4"
                        >
                            Skip for now
                        </v-btn>
                        <v-spacer></v-spacer>
                        <v-btn
                            color="primary"
                            @click="submitResponse"
                            :loading="loading"
                            size="large"
                            variant="elevated"
                            class="px-8"
                        >
                            <v-icon start>mdi-content-save</v-icon>
                            Save Knowledge
                        </v-btn>
                    </v-card-actions>
                </v-card>

                <v-card v-else class="mx-auto text-center elevation-12" max-width="600">
                    <v-card-text class="pa-12">
                        <v-icon size="80" color="success" class="mb-6">mdi-check-circle</v-icon>
                        <h2 class="text-h4 mb-4">Thank you!</h2>
                        <p class="text-h6 mb-6 text-medium-emphasis">Your knowledge has been captured and added to the vault.</p>
                        <v-btn
                            color="primary"
                            size="large"
                            @click="closeWindow"
                            class="px-8"
                        >
                            <v-icon start>mdi-close</v-icon>
                            Close Window
                        </v-btn>
                    </v-card-text>
                </v-card>
            `,
            methods: {
                skipForm() {
                    // Try to close the window, if it fails just hide the content
                    if (window.opener || window.parent !== window) {
                        try {
                            window.close();
                        } catch (e) {
                            // If close fails, just hide the content
                            document.body.innerHTML = '<div style="text-align: center; padding: 50px; font-family: Arial;"><h2>You can close this tab</h2><p>Thank you for your time!</p></div>';
                        }
                    } else {
                        document.body.innerHTML = '<div style="text-align: center; padding: 50px; font-family: Arial;"><h2>You can close this tab</h2><p>Thank you for your time!</p></div>';
                    }
                },
                closeWindow() {
                    this.skipForm();
                },
                async submitResponse() {
                    this.loading = true
                    try {
                        const response = await fetch(window.location.href, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify(this.formData)
                        })

                        if (response.ok) {
                            this.completed = true
                        } else {
                            const error = await response.json()
                            alert('Failed to save response: ' + (error.message || 'Please try again.'))
                        }
                    } catch (error) {
                        alert('An error occurred. Please try again.')
                        console.error(error)
                    } finally {
                        this.loading = false
                    }
                },
                getRepoName() {
                    return this.delivery.github_payload?.repository?.name || 'Unknown Repository'
                },
                getCommitMessage() {
                    const commits = this.delivery.github_payload?.commits || []
                    return commits.length > 0 ? commits[0].message : 'No commit message'
                }
            }
        }

        createApp({
            components: {
                PromptForm
            }
        }).use(vuetify).mount('#app')
    </script>
</body>
</html>