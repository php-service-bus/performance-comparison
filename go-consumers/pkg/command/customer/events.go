package customer

type StoredEvent struct {
	Id string
}

func NewCustomerStoredEvent(id string) *StoredEvent {
	return &StoredEvent{Id: id}
}
