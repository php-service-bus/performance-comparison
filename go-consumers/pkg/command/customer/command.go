package customer

type StoreCommand struct {
	Id    string
	Name  string
	Email string
}

func NewStoreCustomerCommand(id, name, email string) *StoreCommand {
	return &StoreCommand{Id: id, Name: name, Email: email}
}
