import { setLoading } from "./loadingAction";
import { apiBaseURL, stockReportActionType } from "../../constants";
import apiConfig from "../../config/apiConfig";
import { setTotalRecord } from "./totalRecordAction";
import requestParam from "../../shared/requestParam";

export const stockDetailsAdjustmentAction =
    (id, filter = {}, isLoading = true) =>
    async (dispatch) => {
        if (isLoading) {
            dispatch(setLoading(true));
        }
        let url = apiBaseURL.STOCK_ADJUSTMENT_TAB + "?product_id=" + id;
        if (
            !_.isEmpty(filter) &&
            (filter.page ||
                filter.pageSize ||
                filter.search ||
                filter.order_By ||
                filter.created_at)
        ) {
            url += requestParam(filter, null, null, null, url);
        }
        await apiConfig
            .get(url)
            .then((response) => {
                dispatch({
                    type: stockReportActionType.STOCK_DETAILS_ADJUSTMENT_TAB,
                    payload: response.data.data,
                });
                dispatch(setTotalRecord(response.data.meta?.total || 0));
                if (isLoading) {
                    dispatch(setLoading(false));
                }
            })
            .catch(() => {
                if (isLoading) {
                    dispatch(setLoading(false));
                }
            });
    };
