<script setup lang="ts">
import AuthBase from '@/layouts/AuthLayout.vue'
import { Button } from '@/components/ui/button'
import { PinInput, PinInputGroup, PinInputSlot } from '@/components/ui/pin-input'
import { Form, Head } from '@inertiajs/vue3'
import { emailOtp } from '@/routes/auth'
import emailOtpActions from '@/routes/auth/email-otp'
import { computed, onMounted, ref } from 'vue'

const code = ref<number[]>([])
const codeValue = computed<string>(() => code.value.join(''))
const isComplete = computed<boolean>(() => codeValue.value.length === 6)

const props = defineProps<{ email?: string; resendCooldownSeconds?: number }>()
const description = computed<string>(() => props.email
    ? `Nhập mã 6 chữ số được gửi tới ${props.email}`
    : 'Nhập mã 6 chữ số được gửi tới email của bạn'
)
const cooldown = ref<number>(props.resendCooldownSeconds ?? 0)

onMounted(() => {
    if (cooldown.value > 0) {
        const timer = setInterval(() => {
            cooldown.value = Math.max(0, cooldown.value - 1)
            if (cooldown.value === 0) clearInterval(timer)
        }, 1000)
    }
})
</script>

<template>
    <AuthBase title="Xác thực mã OTP" :description="description">
        <Head title="Email OTP" />

        <div class="space-y-6">
            <div class="flex flex-col items-center justify-center space-y-3 text-center">
                <div class="flex w-full items-center justify-center">
                    <PinInput id="otp" placeholder="○" v-model="code" type="number" otp>
                        <PinInputGroup>
                            <PinInputSlot v-for="(id, index) in 6" :key="id" :index="index" autofocus />
                        </PinInputGroup>
                    </PinInput>
                </div>
                <input type="hidden" name="otp" :value="codeValue" />
            </div>
            <div class="flex flex-col gap-3">
                <Form v-bind="emailOtpActions.verify.form()" as="form" preserve-scroll>
                    <input type="hidden" name="otp" :value="codeValue" />
                    <Button type="submit" class="w-full" :disabled="!isComplete">Tiếp tục</Button>
                </Form>
                <Form v-bind="emailOtpActions.send.form()" as="form" preserve-scroll>
                    <Button type="submit" variant="outline" class="w-full" :disabled="cooldown > 0">
                        <span v-if="cooldown > 0">Gửi lại mã ({{ cooldown }}s)</span>
                        <span v-else>Gửi lại mã</span>
                    </Button>
                </Form>
            </div>
        </div>
    </AuthBase>
    
</template>