import { getData } from '@/apis/http'
import { defineStore } from 'pinia';
import { ref } from "vue";

export type RsaPublicKeyInfo = {
    serial: string
    publicKey: string
    expireAt: number
}

/** SKIP_SECONDS 与后端 RsaKeyService 一致，距过期预留秒数 */
const SKIP_SECONDS = 600

export const useSecurityRsa = defineStore('security_rsa', () => {
    const rsaPublicKey = ref<RsaPublicKeyInfo | null>(null)
    let pendingRsaPublicKeyRequest: Promise<RsaPublicKeyInfo | null> | null = null

    const setRsaPublicKey = (data: RsaPublicKeyInfo) => {
        rsaPublicKey.value = data
    }

    const clearRsaPublicKey = () => {
        rsaPublicKey.value = null
    }

    /** forceRefreshPublicKey 清空缓存并重新拉取公钥（40001 重试用） */
    const forceRefreshPublicKey = async (): Promise<RsaPublicKeyInfo | null> => {
        clearRsaPublicKey()
        return await ensureRsaPublicKey()
    }

    const isExpired = (expireAt: number): boolean => {
        return Math.floor(Date.now() / 1000) >= expireAt - SKIP_SECONDS
    }

    const ensureRsaPublicKey = async (): Promise<RsaPublicKeyInfo | null> => {
        if (rsaPublicKey.value && !isExpired(rsaPublicKey.value.expireAt)) {
            return rsaPublicKey.value
        }

        if (pendingRsaPublicKeyRequest) {
            return await pendingRsaPublicKeyRequest
        }

        pendingRsaPublicKeyRequest = (async () => {
            try {
                const res = await getData<{
                    serial: string
                    public_key: string
                    expire_at: number
                }>('Security/rsaPublicKey')
                if (!res.serial || !res.public_key) {
                    clearRsaPublicKey()
                    return null
                }
                setRsaPublicKey({
                    serial: res.serial,
                    publicKey: res.public_key,
                    expireAt: res.expire_at,
                })
                return rsaPublicKey.value
            } catch {
                clearRsaPublicKey()
                return null
            }
        })()

        try {
            return await pendingRsaPublicKeyRequest
        } finally {
            pendingRsaPublicKeyRequest = null
        }
    }

    const getPublicKeyBySerial = async (serial?: string): Promise<RsaPublicKeyInfo | null> => {
        const key = await ensureRsaPublicKey()
        if (!key) return null
        if (!serial) return key
        if (key.serial !== serial) return null
        return key
    }

    const encryptWithPublicKey = async (pem: string, plainText: string): Promise<string> => {
        const spkiDer = pemToArrayBuffer(pem)
        const key = await crypto.subtle.importKey(
            'spki',
            spkiDer,
            {
                name: 'RSA-OAEP',
                hash: 'SHA-1',
            },
            false,
            ['encrypt']
        )
        const cipherBuffer = await crypto.subtle.encrypt(
            {name: 'RSA-OAEP'},
            key,
            new TextEncoder().encode(plainText)
        )
        return arrayBufferToBase64(cipherBuffer)
    }

    const pemToArrayBuffer = (pem: string): ArrayBuffer => {
        const base64 = pem
            .replace('-----BEGIN PUBLIC KEY-----', '')
            .replace('-----END PUBLIC KEY-----', '')
            .replace(/\s+/g, '')
        const binary = window.atob(base64)
        const bytes = new Uint8Array(binary.length)
        for (let i = 0; i < binary.length; i++) {
            bytes[i] = binary.charCodeAt(i)
        }
        return bytes.buffer
    }

    const arrayBufferToBase64 = (buffer: ArrayBuffer): string => {
        const bytes = new Uint8Array(buffer)
        let binary = ''
        for (let i = 0; i < bytes.length; i++) {
            binary += String.fromCharCode(bytes[i] ?? 0)
        }
        return window.btoa(binary)
    }

    const encryptText = async (plainText: string, serial?: string): Promise<null | {
        serial: string
        cipherText: string
    }> => {
        const key = await getPublicKeyBySerial(serial)
        if (!key) return null
        const cipherText = await encryptWithPublicKey(key.publicKey, plainText)
        return {
            serial: key.serial,
            cipherText
        }
    }

    /** encrypt 批量 RSA-OAEP 加密（同一批次共用当前公钥；可变参数传入 1 个或多个明文） */
    const encrypt = async (...plain_texts: string[]): Promise<{
        serial: string
        texts: string[]
    }> => {
        const key = await getPublicKeyBySerial()
        if (!key) {
            return {
                serial: "",
                texts: plain_texts.map(() => "")
            }
        }
        return {
            serial: key.serial,
            texts: await Promise.all(plain_texts.map((text) => encryptWithPublicKey(key.publicKey, text)))
        }
    }

    return {
        rsaPublicKey,
        setRsaPublicKey,
        clearRsaPublicKey,
        forceRefreshPublicKey,
        isExpired,
        ensureRsaPublicKey,
        getPublicKeyBySerial,
        encryptText,
        encrypt
    }
}, {
    // persist: true
})
