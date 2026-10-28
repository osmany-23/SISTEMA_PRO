import {setLoading} from './loadingAction';
import apiConfig from '../../config/apiConfig';
import {apiBaseURL, toastType} from '../../constants';
import {addToast} from './toastAction';

export const quotationPdfAction = (quotationId, isLoading = true) => async (dispatch) => {
    if (isLoading) {
        dispatch(setLoading(true))
    }
    apiConfig.get(apiBaseURL.QUOTATION_PDF + '/' + quotationId)
        .then((response) => {
            const pdfUrl = response.data.data.quotation_pdf_url;
            window.open(`${pdfUrl}${pdfUrl.includes('?') ? '&' : '?'}v=${Date.now()}`, '_blank');
            if (isLoading) {
                dispatch(setLoading(false))
            }
        })
        .catch(({response}) => {
            if (isLoading) {
                dispatch(setLoading(false))
            }
            dispatch(addToast(
                {text: response?.data?.message || 'No se pudo generar el PDF de la cotizacion.', type: toastType.ERROR}));
        });
};
