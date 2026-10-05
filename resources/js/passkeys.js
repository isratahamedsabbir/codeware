/*
 |  WebAuthn client for laravel/passkeys, exposed as window.Passkeys.
 |
 |  Not a dependency: the surface used here is small and stable — turn the
 |  options JSON the server produced into something navigator.credentials
 |  accepts, then hand the credential back as JSON. Both directions are one
 |  Web API each (PublicKeyCredential.parseCreationOptionsFromJSON /
 |  parseRequestOptionsFromJSON, and PublicKeyCredential#toJSON), with a manual
 |  base64url fallback for the browsers that predate them. That fallback is the
 |  bulk of this file and it is here rather than in a package because a package
 |  would be the only dependency on the admin bundle and it would be a copy of
 |  roughly the same twenty lines.
 |
 |  Only the admin bundle loads this (see app.js) — enrolment happens in the
 |  admin panel and the portals, never on a storefront page.
 |
 |  Credentials are sent as POST form fields, matching how Laravel's passkey
 |  controllers expect them (credential.rawId, credential.response.*), and
 |  errors come back as Laravel's own { errors: { ... } } shape.
 */

const b64url = {
    decode(value) {
        const padded = value.replace(/-/g, '+').replace(/_/g, '/')
            + '='.repeat((4 - (value.length % 4)) % 4);

        const binary = atob(padded);
        const bytes = new Uint8Array(binary.length);

        for (let i = 0; i < binary.length; i++) bytes[i] = binary.charCodeAt(i);

        return bytes.buffer;
    },

    encode(buffer) {
        const bytes = new Uint8Array(buffer);

        let binary = '';
        for (let i = 0; i < bytes.length; i++) binary += String.fromCharCode(bytes[i]);

        return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
    },
};

/** The credential descriptors, which arrive as base64url strings. */
function descriptors(list = []) {
    return list.map((descriptor) => ({
        ...descriptor,
        id: b64url.decode(descriptor.id),
    }));
}

function decodeCreationOptions(options) {
    if (window.PublicKeyCredential?.parseCreationOptionsFromJSON) {
        return window.PublicKeyCredential.parseCreationOptionsFromJSON(options);
    }

    return {
        ...options,
        challenge: b64url.decode(options.challenge),
        user: { ...options.user, id: b64url.decode(options.user.id) },
        excludeCredentials: descriptors(options.excludeCredentials),
    };
}

function decodeRequestOptions(options) {
    if (window.PublicKeyCredential?.parseRequestOptionsFromJSON) {
        return window.PublicKeyCredential.parseRequestOptionsFromJSON(options);
    }

    return {
        ...options,
        challenge: b64url.decode(options.challenge),
        allowCredentials: descriptors(options.allowCredentials),
    };
}

/** True when this browser can do WebAuthn at all. */
export function supported() {
    return typeof window !== 'undefined'
        && !!window.PublicKeyCredential
        && !!navigator.credentials;
}

/** AbortError is the user closing the system prompt — not worth reporting. */
function isAbort(error) {
    return error?.name === 'AbortError' || error?.name === 'NotAllowedError';
}

function message(error) {
    if (isAbort(error)) return 'Cancelled.';

    if (error?.name === 'InvalidStateError') {
        return 'That authenticator is already registered on this account.';
    }

    return error?.message || 'Something went wrong. Try again.';
}

async function parse(response) {
    if (typeof response?.toJSON === 'function') return response.toJSON();

    // Pre-2023 browsers: assemble the object the server's fromJson() expects.
    return {
        id: response.id,
        rawId: b64url.encode(response.rawId),
        type: response.type,
        response: {
            clientDataJSON: b64url.encode(response.response.clientDataJSON),
            authenticatorData: b64url.encode(response.response.authenticatorData),
            signature: b64url.encode(response.response.signature),
            userHandle: response.response.userHandle
                ? b64url.encode(response.response.userHandle)
                : null,
        },
    };
}

function parseAssertion(response) {
    if (typeof response?.toJSON === 'function') return response.toJSON();

    return {
        id: response.id,
        rawId: b64url.encode(response.rawId),
        type: response.type,
        response: {
            clientDataJSON: b64url.encode(response.response.clientDataJSON),
            authenticatorData: b64url.encode(response.response.authenticatorData),
            signature: b64url.encode(response.response.signature),
            userHandle: response.response.userHandle
                ? b64url.encode(response.response.userHandle)
                : null,
        },
    };
}

export const Passkeys = {
    supported,

    /**
     * Registration ceremony. `options` is the JSON string the server produced.
     * Returns the credential as a plain object, or { error } on a failure the
     * caller is expected to show rather than throw.
     */
    async create(options) {
        try {
            const credential = await navigator.credentials.create({
                publicKey: decodeCreationOptions(JSON.parse(options)),
            });

            return { credential: await parse(credential) };
        } catch (error) {
            return { error: message(error) };
        }
    },

    /** Verification (assertion) ceremony — answering a second-factor challenge. */
    async get(options) {
        try {
            const credential = await navigator.credentials.get({
                publicKey: decodeRequestOptions(JSON.parse(options)),
            });

            // Awaited like create()'s, so a caller can JSON.stringify the whole
            // result rather than having to know that .credential is a promise.
            return { credential: await parseAssertion(credential) };
        } catch (error) {
            return { error: message(error) };
        }
    },
};

window.Passkeys = Passkeys;

export default Passkeys;